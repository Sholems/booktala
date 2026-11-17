<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class BookingCheckinReminder extends Notification
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
            ->subject('Check-in Tomorrow - ' . $this->reservation->booking_ref)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your check-in is tomorrow!')
            ->line('Booking Reference: ' . $this->reservation->booking_ref)
            ->line('Property: ' . $property->title)
            ->line('Address: ' . $property->address . ', ' . $property->city . ', ' . $property->state)
            ->line('Check-in Time: ' . ($property->check_in_time ?? '2:00 PM'))
            ->line('Check-out Time: ' . ($property->check_out_time ?? '12:00 PM'));

        if ($property->check_in_instructions) {
            $mail->line('Special Instructions: ' . $property->check_in_instructions);
        }

        if (!$this->reservation->guestCheckin) {
            $mail->line('Save time at the front desk: complete online check-in before you arrive.')
                ->action('Check in online', route('customer.checkin.create', $this->reservation->id));
        }

        return $mail
            ->line('What to bring:')
            ->line('- Valid ID')
            ->line('- Confirmation email or booking reference')
            ->line('View your booking: ' . route('customer.reservations.show', $this->reservation->id))
            ->line('We look forward to hosting you!');
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Check-in Reminder',
            'message' => 'Your check-in is tomorrow for booking ' . $this->reservation->booking_ref,
            'booking_ref' => $this->reservation->booking_ref,
        ];
    }
}