<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelled extends Notification
{
    use Queueable;

    public Reservation $reservation;

    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $property = $this->reservation->property;

        $mail = (new MailMessage)
            ->subject('Booking Cancelled - '.$this->reservation->booking_ref)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your booking has been cancelled.')
            ->line('Booking Reference: '.$this->reservation->booking_ref)
            ->line('Property: '.$property->title)
            ->line('Check-in: '.$this->reservation->check_in->format('F j, Y'))
            ->line('Check-out: '.$this->reservation->check_out->format('F j, Y'));

        if ($this->reservation->cancellation_reason) {
            $mail->line('Reason: '.$this->reservation->cancellation_reason);
        }

        if ($this->reservation->refund_status === 'pending') {
            $mail->line('Your booking refund is being reviewed under our cancellation policy. The processing fee is non-refundable, and the caution fee is handled separately.');
        }

        return $mail
            ->line('If you have any questions, please contact us.')
            ->action('Browse Properties', route('properties.index'));
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Booking Cancelled',
            'message' => 'Your booking '.$this->reservation->booking_ref.' has been cancelled.',
            'booking_ref' => $this->reservation->booking_ref,
        ];
    }
}
