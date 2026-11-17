<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells admins that a guest cancelled their own booking, and whether a refund needs reviewing.
 */
class GuestCancelledBooking extends Notification
{
    use Queueable;

    public function __construct(public Reservation $reservation)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $r = $this->reservation;

        $mail = (new MailMessage)
            ->subject('Guest cancelled booking ' . $r->booking_ref)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line(($r->user->name ?? 'A guest') . ' cancelled booking ' . $r->booking_ref . '.')
            ->line('Apartment: ' . ($r->property->title ?? '-'))
            ->line('Dates: ' . $r->check_in->format('j M Y') . ' to ' . $r->check_out->format('j M Y'));

        if ($r->cancellation_reason) {
            $mail->line('Reason given: ' . $r->cancellation_reason);
        }

        if ($r->refund_status === 'pending') {
            $mail->line('This booking was paid (₦' . number_format((float) $r->total_amount) . '). Review the refund under the cancellation policy.');
        }

        return $mail->action('Open booking', route('admin.reservations.show', $r));
    }

    public function toArray($notifiable): array
    {
        return [
            'reservation_id' => $this->reservation->id,
            'booking_ref' => $this->reservation->booking_ref,
            'refund_status' => $this->reservation->refund_status,
            'message' => 'Guest cancelled booking ' . $this->reservation->booking_ref,
        ];
    }
}
