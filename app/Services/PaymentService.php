<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Payment;
use App\Notifications\BookingConfirmed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    private $paystackSecretKey;

    public function __construct()
    {
        $this->paystackSecretKey = config('services.paystack.secret');

        if (empty($this->paystackSecretKey)) {
            Log::error('Paystack secret key is not configured');
            throw new \Exception('Payment system is not configured. Please contact support.');
        }
    }

    public function initializePayment(Reservation $reservation, $email)
    {
        $amount = (int) round($reservation->total_amount * 100);

        $response = Http::withToken($this->paystackSecretKey)
            ->post('https://api.paystack.co/transaction/initialize', [
                'email' => $email,
                'amount' => $amount,
                'currency' => 'NGN',
                'reference' => $reservation->booking_ref,
                'callback_url' => route('booking.callback'),
                'metadata' => [
                    'reservation_id' => $reservation->id,
                    'booking_ref' => $reservation->booking_ref,
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

    public function verifyPayment($reference)
    {
        $response = Http::withToken($this->paystackSecretKey)
            ->get('https://api.paystack.co/transaction/verify/' . rawurlencode($reference));

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Failed to verify payment');
    }

    public function handleWebhook(string $rawPayload, string $signature)
    {
        if (!$this->verifySignature($rawPayload, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload = json_decode($rawPayload, true) ?: [];
        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];

        switch ($event) {
            case 'charge.success':
                $this->markPaymentSuccessful($data);
                break;
            case 'refund.processed':
            case 'refund':
                $this->handleRefund($data);
                break;
        }

        return response()->json(['success' => true]);
    }

    /**
     * Record a verified Paystack charge and confirm its reservation.
     * Safe to call from both the redirect callback and the webhook.
     */
    public function markPaymentSuccessful(array $data): ?Reservation
    {
        $reference = $data['reference'] ?? null;

        if (!$reference || ($data['status'] ?? null) !== 'success') {
            return null;
        }

        return DB::transaction(function () use ($data, $reference) {
            $payment = Payment::where('paystack_reference', $reference)->lockForUpdate()->first();

            $reservation = $payment?->reservation
                ?? Reservation::where('booking_ref', $reference)->lockForUpdate()->first();

            if (!$reservation) {
                Log::warning('Paystack payment for unknown reservation', ['reference' => $reference]);
                return null;
            }

            $paidAmount = ($data['amount'] ?? 0) / 100;
            $expectedAmount = (float) $reservation->total_amount;

            if (($data['currency'] ?? 'NGN') !== 'NGN' || $paidAmount + 0.01 < $expectedAmount) {
                Log::error('Paystack amount mismatch', [
                    'reference' => $reference,
                    'paid' => $paidAmount,
                    'expected' => $expectedAmount,
                ]);
                return null;
            }

            if (!$payment) {
                $payment = $this->createPayment($reservation, $reference, $paidAmount);
            }

            $payment->update([
                'amount' => $paidAmount,
                'paystack_status' => $data['status'],
                'payment_method' => $data['channel'] ?? 'card',
            ]);

            // Money arriving for a booking that was cancelled meanwhile must not revive it:
            // record the payment and flag it for a refund instead.
            if (in_array($reservation->booking_status, ['cancelled', 'no_show'], true)) {
                $reservation->update([
                    'payment_status' => 'paid',
                    'payment_method' => $data['channel'] ?? 'card',
                    'paystack_reference' => $reference,
                    'refund_status' => $reservation->refund_status ?? 'pending',
                ]);

                return $reservation;
            }

            $alreadyConfirmed = $reservation->payment_status === 'paid'
                && $reservation->booking_status === 'confirmed';

            if (!$alreadyConfirmed) {
                $reservation->update([
                    'payment_status' => 'paid',
                    'payment_method' => $data['channel'] ?? 'card',
                    'paystack_reference' => $reference,
                    'booking_status' => 'confirmed',
                    'confirmed_at' => now(),
                ]);

                // A mail failure must not roll back a payment that already succeeded.
                try {
                    $reservation->user?->notify(new BookingConfirmed($reservation));
                } catch (\Throwable $e) {
                    Log::error('Booking confirmation notification failed', [
                        'reservation_id' => $reservation->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return $reservation;
        });
    }

    private function handleRefund($data)
    {
        $reference = $data['transaction_reference']
            ?? ($data['transaction']['reference'] ?? ($data['transaction'] ?? null));

        if (!is_string($reference)) {
            return;
        }

        $payment = Payment::where('paystack_reference', $reference)->first();

        if ($payment) {
            $payment->update([
                'type' => 'refund',
                'paystack_status' => 'refunded',
            ]);

            $payment->reservation->update([
                'payment_status' => 'refunded',
            ]);
        }
    }

    private function verifySignature(string $rawPayload, string $signature): bool
    {
        if ($signature === '') {
            return false;
        }

        $expectedSignature = hash_hmac('sha512', $rawPayload, $this->paystackSecretKey);
        return hash_equals($expectedSignature, $signature);
    }

    public function createPayment(Reservation $reservation, $paystackReference, $amount)
    {
        return Payment::firstOrCreate(['paystack_reference' => $paystackReference], [
            'reservation_id' => $reservation->id,
            'user_id' => $reservation->user_id,
            'amount' => $amount,
            'currency' => 'NGN',
            'type' => 'payment',
        ]);
    }
}
