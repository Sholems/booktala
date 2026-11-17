<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\NotificationLog;
use App\Notifications\BookingConfirmed;
use Illuminate\Support\Facades\DB;

class BookingService
{
    private PricingService $pricingService;
    private PaymentService $paymentService;

    public function __construct(PricingService $pricingService, PaymentService $paymentService)
    {
        $this->pricingService = $pricingService;
        $this->paymentService = $paymentService;
    }

    public function createReservation($user, Property $property, array $data)
    {
        DB::beginTransaction();

        try {
            $pricing = $this->pricingService->calculatePrice(
                $property,
                $data['check_in'],
                $data['check_out'],
                $data['guests'],
                $data['discount_code'] ?? null
            );

            $reservation = Reservation::create([
                'booking_ref' => Reservation::generateBookingRef(),
                'user_id' => $user->id,
                'property_id' => $property->id,
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'nights' => $pricing['nights'],
                'guests' => $pricing['guests'],
                'subtotal' => $pricing['subtotal'],
                'discount_amount' => $pricing['discount_amount'],
                'tax_amount' => $pricing['tax_amount'],
                'total_amount' => $pricing['total_amount'],
                'payment_status' => 'pending',
                'booking_status' => 'pending',
                'special_requests' => $data['special_requests'] ?? null,
            ]);

            DB::commit();

            return $reservation;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create a pending reservation, open a Paystack transaction for it and
     * record the pending payment. Returns the reservation and checkout URL.
     */
    public function startBooking($user, Property $property, array $data): array
    {
        $reservation = $this->createReservation($user, $property, $data);

        $paymentData = $this->paymentService->initializePayment($reservation, $user->email);

        $this->paymentService->createPayment(
            $reservation,
            $paymentData['data']['reference'] ?? $reservation->booking_ref,
            $reservation->total_amount
        );

        return [$reservation, $paymentData['data']['authorization_url']];
    }

    public function confirmReservation(Reservation $reservation)
    {
        $reservation->update([
            'booking_status' => 'confirmed',
            'confirmed_by' => auth()->id(),
            'confirmed_at' => now(),
        ]);

        $this->sendConfirmationNotification($reservation);
    }

    public function checkIn(Reservation $reservation, $staffId)
    {
        $reservation->update([
            'booking_status' => 'checked_in',
        ]);

        $this->logActivity($reservation->user, 'check_in', $reservation);
    }

    public function checkOut(Reservation $reservation, $staffId)
    {
        $reservation->update([
            'booking_status' => 'checked_out',
        ]);

        $this->logActivity($reservation->user, 'check_out', $reservation);
    }

    public function cancelReservation(Reservation $reservation, $reason, $userId)
    {
        $reservation->update([
            'booking_status' => 'cancelled',
            'payment_status' => 'refunded',
            'cancellation_reason' => $reason,
            'cancelled_by' => $userId,
            'cancelled_at' => now(),
        ]);
    }

    private function sendConfirmationNotification(Reservation $reservation)
    {
        $user = $reservation->user;
        
        $user->notify(new BookingConfirmed($reservation));

        NotificationLog::create([
            'user_id' => $user->id,
            'type' => 'booking_confirmation',
            'title' => 'Booking Confirmed',
            'message' => "Your booking {$reservation->booking_ref} has been confirmed.",
            'channel' => 'email',
        ]);
    }

    private function logActivity($user, $action, $reservation)
    {
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'model_type' => Reservation::class,
            'model_id' => $reservation->id,
        ]);
    }
}