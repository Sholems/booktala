<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class BookingService
{
    private PricingService $pricingService;

    private PaymentService $paymentService;

    public function __construct(PricingService $pricingService, PaymentService $paymentService)
    {
        $this->pricingService = $pricingService;
        $this->paymentService = $paymentService;
    }

    public function createReservation(User $user, Property $property, array $data): Reservation
    {
        return Cache::lock("booking:property:{$property->id}", 10)->block(5, function () use ($user, $property, $data) {
            return DB::transaction(function () use ($user, $property, $data) {
                Property::whereKey($property->getKey())->lockForUpdate()->firstOrFail();

                $pricing = $this->pricingService->calculatePrice(
                    $property,
                    $data['check_in'],
                    $data['check_out'],
                    $data['guests'],
                    $data['discount_code'] ?? null,
                );

                return Reservation::create([
                    'booking_ref' => Reservation::generateBookingRef(),
                    'user_id' => $user->id,
                    'property_id' => $property->id,
                    'check_in' => $data['check_in'],
                    'check_out' => $data['check_out'],
                    'nights' => $pricing['nights'],
                    'guests' => $pricing['guests'],
                    'subtotal' => $pricing['subtotal'],
                    'discount_amount' => $pricing['discount_amount'],
                    'discount_code' => $pricing['discount_code'],
                    'tax_amount' => $pricing['tax_amount'],
                    'processing_fee' => $pricing['processing_fee'],
                    'caution_fee' => $pricing['caution_fee'],
                    'caution_fee_status' => 'pending',
                    'total_amount' => $pricing['total_amount'],
                    'payment_status' => 'pending',
                    'booking_status' => 'pending',
                    'hold_expires_at' => now()->addMinutes(config('booking.hold_minutes')),
                    'special_requests' => $data['special_requests'] ?? null,
                ]);
            });
        });
    }

    /**
     * Create a pending reservation, open a Paystack transaction for it and
     * record the pending payment. Returns the reservation and checkout URL.
     */
    public function startBooking(User $user, Property $property, array $data): array
    {
        $reservation = $this->createReservation($user, $property, $data);

        try {
            $partial = ($data['payment_mode'] ?? 'full') === 'partial';
            $paymentData = $this->paymentService->initializePayment(
                $reservation,
                $user->email,
                $partial ? round((float) $reservation->total_amount * 0.5, 2) : null,
            );

            $this->paymentService->createPayment(
                $reservation,
                $paymentData['data']['reference'] ?? $reservation->booking_ref,
                $partial ? round((float) $reservation->total_amount * 0.5, 2) : $reservation->total_amount,
            );
        } catch (Throwable $exception) {
            $reservation->update([
                'booking_status' => 'cancelled',
                'cancellation_reason' => 'Payment initialization failed',
                'cancelled_at' => now(),
                'hold_expires_at' => null,
            ]);

            throw $exception;
        }

        return [$reservation, $paymentData['data']['authorization_url']];
    }

    public function startBalancePayment(Reservation $reservation): array
    {
        $paidAmount = (float) $reservation->payments()
            ->where('type', 'payment')
            ->where('paystack_status', 'success')
            ->sum('amount');
        $balance = round((float) $reservation->total_amount - $paidAmount, 2);

        if ($reservation->payment_status !== 'partial' || $balance <= 0) {
            throw new \Exception('This booking does not have a balance to pay.');
        }

        $reference = $reservation->booking_ref.'-BAL-'.strtoupper(substr(bin2hex(random_bytes(6)), 0, 12));
        $paymentData = $this->paymentService->initializePayment(
            $reservation,
            $reservation->user->email,
            $balance,
            $reference,
        );
        $this->paymentService->createPayment($reservation, $reference, $balance);

        return [$reservation, $paymentData['data']['authorization_url']];
    }
}
