<?php

namespace App\Listeners;

use App\Events\ReservationStatusChanged;
use App\Models\User;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingPending;
use App\Notifications\GuestCancelledBooking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendReservationNotification implements ShouldQueue
{
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
                break;

            case 'pending':
                $user->notify(new BookingPending($reservation));
                break;

            case 'cancelled':
                $user->notify(new BookingCancelled($reservation));

                // When the guest cancelled it themselves, the team needs to know too.
                if ($event->actorId !== null && $event->actorId === $reservation->user_id) {
                    $admins = User::role(['super_admin', 'admin'])->where('id', '!=', $user->id)->get();
                    Notification::send($admins, new GuestCancelledBooking($reservation));
                }
                break;

            case 'checked_in':
                // Send check-in reminder (already checked in)
                break;
        }
    }
}
