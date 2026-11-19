<?php

namespace App\Services;

use App\Models\DiscountCode;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Reservation;
use App\Services\Finance\LedgerPostingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    private ?string $paystackSecretKey;

    public function __construct(private ReservationLifecycle $reservationLifecycle)
    {
        $secret = config('services.paystack.secret');
        $this->paystackSecretKey = is_string($secret) && $secret !== '' ? $secret : null;
    }

    public function initializePayment(Reservation $reservation, string $email, ?float $amountOverride = null, ?string $referenceOverride = null): array
    {
        $secret = $this->requireSecret();
        $amountDue = $amountOverride === null
            ? (float) $reservation->total_amount
            : max(1, min((float) $reservation->total_amount, $amountOverride));
        $amount = (int) round($amountDue * 100);
        $reference = $referenceOverride ?: $reservation->booking_ref;

        $response = Http::withToken($secret)
            ->post('https://api.paystack.co/transaction/initialize', [
                'email' => $email,
                'amount' => $amount,
                'currency' => 'NGN',
                'reference' => $reference,
                'callback_url' => route('booking.callback'),
                'metadata' => [
                    'reservation_id' => $reservation->id,
                    'booking_ref' => $reservation->booking_ref,
                    'payment_reference' => $reference,
                ],
            ]);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Paystack initialization failed', [
            'response' => $response->json(),
            'reservation_id' => $reservation->id,
            'amount' => $amount,
        ]);
        throw new \Exception('Failed to initialize payment. Please try again or contact support.');
    }

    public function verifyPayment(string $reference): array
    {
        $secret = $this->requireSecret();
        $response = Http::withToken($secret)
            ->get('https://api.paystack.co/transaction/verify/'.rawurlencode($reference));

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Failed to verify payment');
    }

    public function handleWebhook(string $rawPayload, string $signature): JsonResponse
    {
        if (! $this->verifySignature($rawPayload, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload = json_decode($rawPayload, true) ?: [];
        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];

        $eventId = $data['id'] ?? null;
        $eventKey = is_string($eventId) || is_int($eventId)
            ? hash('sha256', 'paystack:'.$eventId)
            : hash('sha256', 'paystack:'.hash('sha256', $rawPayload));

        return Cache::lock('payment-event:'.$eventKey, 30)->block(5, function () use ($eventKey, $event, $payload, $data) {
            $paymentEvent = PaymentEvent::firstOrCreate(
                ['event_key' => $eventKey],
                [
                    'provider' => 'paystack',
                    'event_type' => is_string($event) ? $event : null,
                    'payload' => $payload,
                    'processed_at' => null,
                ],
            );

            if ($paymentEvent->processed_at !== null) {
                return response()->json(['success' => true, 'duplicate' => true]);
            }

            switch ($event) {
                case 'charge.success':
                    $this->markPaymentSuccessful($data);
                    break;
                case 'refund.processed':
                case 'refund':
                    $this->handleRefund($data);
                    break;
            }

            $paymentEvent->update(['processed_at' => now()]);

            return response()->json(['success' => true]);
        });
    }

    /**
     * Record a verified Paystack charge and confirm its reservation.
     * Safe to call from both the redirect callback and the webhook.
     */
    public function markPaymentSuccessful(array $data): ?Reservation
    {
        $reference = $data['reference'] ?? null;

        if (! $reference || ($data['status'] ?? null) !== 'success') {
            return null;
        }

        return DB::transaction(function () use ($data, $reference) {
            $payment = Payment::where('paystack_reference', $reference)->lockForUpdate()->first();

            $reservation = $payment?->reservation
                ?? Reservation::where('booking_ref', $reference)->lockForUpdate()->first();

            if (! $reservation) {
                Log::warning('Paystack payment for unknown reservation', ['reference' => $reference]);

                return null;
            }

            $paidAmount = ($data['amount'] ?? 0) / 100;
            $expectedAmount = (float) $reservation->total_amount;

            $expectedPartialAmount = round($expectedAmount * 0.5, 2);
            $expectedPaymentAmount = $payment
                ? (float) $payment->amount
                : $expectedPartialAmount;
            if (($data['currency'] ?? 'NGN') !== 'NGN'
                || $paidAmount + 0.01 < $expectedPaymentAmount) {
                Log::error('Paystack amount mismatch', [
                    'reference' => $reference,
                    'paid' => $paidAmount,
                    'minimum_partial' => $expectedPartialAmount,
                    'expected_full' => $expectedAmount,
                ]);

                return null;
            }

            if (! $payment) {
                $payment = $this->createPayment($reservation, $reference, $paidAmount);
            }

            $payment->update([
                'amount' => $paidAmount,
                'paystack_status' => $data['status'],
                'payment_method' => $data['channel'] ?? 'card',
            ]);

            $successfulPayments = (float) Payment::where('reservation_id', $reservation->id)
                ->where('type', 'payment')
                ->where('paystack_status', 'success')
                ->sum('amount');
            $paymentStatus = $successfulPayments + 0.01 >= $expectedAmount ? 'paid' : 'partial';

            // Money arriving for a booking that was cancelled meanwhile must not revive it:
            // record the payment and flag it for a refund instead.
            if (in_array($reservation->booking_status, ['cancelled', 'no_show'], true)) {
                $reservation->update([
                    'payment_status' => $paymentStatus,
                    'payment_method' => $data['channel'] ?? 'card',
                    'paystack_reference' => $reference,
                    'refund_status' => $reservation->refund_status ?? 'pending',
                    'caution_fee_status' => (float) $reservation->caution_fee > 0 ? 'refund_pending' : null,
                    'hold_expires_at' => null,
                ]);

                return $reservation;
            }

            $alreadyConfirmed = in_array($reservation->payment_status, ['paid', 'partial'], true)
                && $reservation->booking_status === 'confirmed';
            $wasRecorded = in_array($reservation->payment_status, ['paid', 'partial'], true);
            $wasPending = $reservation->payment_status === 'pending';

            if (! $wasRecorded || $reservation->payment_status !== $paymentStatus) {
                $reservation->update([
                    'payment_status' => $paymentStatus,
                    'payment_method' => $data['channel'] ?? 'card',
                    'paystack_reference' => $reference,
                ]);

                if ($wasPending && $reservation->discount_code) {
                    DiscountCode::where('code', $reservation->discount_code)->increment('used_count');
                }
            }

            if (! $alreadyConfirmed && $reservation->booking_status === 'pending') {
                $this->reservationLifecycle->confirmAfterPayment($reservation);
            } elseif ($reservation->booking_status === 'confirmed') {
                $reservation->update([
                    'hold_expires_at' => null,
                    'caution_fee_status' => $paymentStatus === 'paid' && (float) $reservation->caution_fee > 0
                        ? 'held'
                        : $reservation->caution_fee_status,
                ]);
            }

            app(LedgerPostingService::class)->postCollection($payment);

            return $reservation;
        });
    }

    private function handleRefund(array $data): void
    {
        $reference = $data['transaction_reference']
            ?? ($data['transaction']['reference'] ?? ($data['transaction'] ?? null));

        if (! is_string($reference)) {
            return;
        }

        DB::transaction(function () use ($data, $reference) {
            $payment = Payment::where('paystack_reference', $reference)
                ->where('type', 'payment')
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                return;
            }

            $refundAmount = isset($data['amount'])
                ? round((float) $data['amount'] / 100, 2)
                : (float) $payment->amount;
            $refundId = $data['id'] ?? hash('sha256', json_encode($data));

            Payment::firstOrCreate(
                ['paystack_reference' => 'REF-'.(string) $refundId],
                [
                    'reservation_id' => $payment->reservation_id,
                    'user_id' => $payment->user_id,
                    'amount' => $refundAmount,
                    'currency' => $data['currency'] ?? $payment->currency,
                    'paystack_status' => 'refunded',
                    'payment_method' => $payment->payment_method,
                    'type' => 'refund',
                    'metadata' => ['original_reference' => $reference],
                ],
            );

            $totalPaid = (float) Payment::where('reservation_id', $payment->reservation_id)
                ->where('type', 'payment')
                ->where('paystack_status', 'success')
                ->sum('amount');
            $totalRefunded = (float) Payment::where('reservation_id', $payment->reservation_id)
                ->where('type', 'refund')
                ->where('paystack_status', 'refunded')
                ->sum('amount');

            $payment->reservation->update([
                'payment_status' => $totalRefunded + 0.01 >= $totalPaid ? 'refunded' : 'partial',
            ]);
        });
    }

    private function verifySignature(string $rawPayload, string $signature): bool
    {
        if ($signature === '' || $this->paystackSecretKey === null) {
            return false;
        }

        $expectedSignature = hash_hmac('sha512', $rawPayload, $this->paystackSecretKey);

        return hash_equals($expectedSignature, $signature);
    }

    private function requireSecret(): string
    {
        if ($this->paystackSecretKey === null) {
            Log::error('Paystack secret key is not configured');
            throw new \Exception('Payment system is not configured. Please contact support.');
        }

        return $this->paystackSecretKey;
    }

    public function createPayment(Reservation $reservation, string $paystackReference, float|int|string $amount): Payment
    {
        return Payment::firstOrCreate(['paystack_reference' => $paystackReference], [
            'reservation_id' => $reservation->id,
            'user_id' => $reservation->user_id,
            'guest_id' => $reservation->guest_id,
            'provider' => 'paystack',
            'reference' => $paystackReference,
            'amount' => $amount,
            'currency' => 'NGN',
            'type' => 'payment',
        ]);
    }

    public function applySuccessfulPayment(Payment $payment, Reservation $reservation): Reservation
    {
        $successfulPayments = (float) Payment::where('reservation_id', $reservation->id)
            ->where('type', 'payment')
            ->where('paystack_status', 'success')
            ->sum('amount');
        $status = $successfulPayments + 0.01 >= (float) $reservation->total_amount ? 'paid' : 'partial';

        $reservation->update([
            'payment_status' => $status,
            'payment_method' => $payment->payment_method,
            'hold_expires_at' => $status === 'paid' ? null : $reservation->hold_expires_at,
            'caution_fee_status' => $status === 'paid' && (float) $reservation->caution_fee > 0 ? 'held' : $reservation->caution_fee_status,
        ]);

        if ($reservation->booking_status === 'pending' && $status !== 'pending') {
            $this->reservationLifecycle->confirmAfterPayment($reservation);
        }

        app(LedgerPostingService::class)->postCollection($payment);

        return $reservation->fresh();
    }
}
