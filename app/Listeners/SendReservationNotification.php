<?php

namespace App\Listeners;

use App\Events\ReservationStatusChanged;
use App\Models\User;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingPending;
use App\Notifications\GuestCancelledBooking;
use App\Services\WhatsApp\WhatsAppNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendReservationNotification implements ShouldQueue
{
    public function __construct(
        protected WhatsAppNotificationService $whatsAppService
    ) {}

    public function handle(ReservationStatusChanged $event)
    {
        $reservation = $event->reservation;
        $user = $reservation->user;

        if (! $user) {
            return;
        }

        switch ($event->newStatus) {
            case 'confirmed':
                $user->notify(new BookingConfirmed($reservation));
                try {
                    $this->whatsAppService->sendBookingConfirmation($reservation);
                } catch (\Throwable) {
                    // Non-blocking
                }
                break;

            case 'pending':
                $user->notify(new BookingPending($reservation));
                break;

            case 'cancelled':
                $user->notify(new BookingCancelled($reservation));

                if ($event->actorId !== null && $event->actorId === $reservation->user_id) {
                    $admins = User::role(['super_admin', 'admin'])->where('id', '!=', $user->id)->get();
                    Notification::send($admins, new GuestCancelledBooking($reservation));
                }
                break;

            case 'checked_in':
                try {
                    $this->whatsAppService->sendCheckinInstructions($reservation);
                } catch (\Throwable) {
                    // Non-blocking
                }
                break;
        }
    }
}
