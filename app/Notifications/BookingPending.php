<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingPending extends Notification
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

        return (new MailMessage)
            ->subject('Booking Pending - '.$this->reservation->booking_ref)
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Your booking is awaiting confirmation.')
            ->line('Booking Reference: '.$this->reservation->booking_ref)
            ->line('Property: '.$property->title)
            ->line('Check-in: '.$this->reservation->check_in->format('F j, Y'))
            ->line('Check-out: '.$this->reservation->check_out->format('F j, Y'))
            ->line('Total Amount: ₦'.number_format($this->reservation->total_amount, 2))
            ->line('We will notify you once your booking is confirmed.')
            ->action('View Booking', route('customer.reservations.show', $this->reservation->id));
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Booking Pending',
            'message' => 'Your booking '.$this->reservation->booking_ref.' is pending confirmation.',
            'booking_ref' => $this->reservation->booking_ref,
        ];
    }
}
