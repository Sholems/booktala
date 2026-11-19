<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public Payment $payment;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $reservation = $this->payment->reservation;

        $mail = (new MailMessage)
            ->subject('Payment Confirmed - '.($reservation->booking_ref ?? 'Booking'))
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Your payment has been successfully processed.')
            ->line('Payment Reference: '.$this->payment->reference)
            ->line('Amount: ₦'.number_format($this->payment->amount, 2))
            ->line('Payment Method: '.ucfirst($this->payment->payment_method));

        if ($reservation) {
            $mail->line('Booking Reference: '.$reservation->booking_ref)
                ->line('Property: '.$reservation->property->title)
                ->action('View Receipt', route('customer.reservations.show', $reservation->id));
        }

        return $mail->line('Thank you for your booking!');
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Payment Confirmed',
            'message' => 'Payment of ₦'.number_format($this->payment->amount, 2).' has been processed.',
            'reference' => $this->payment->reference,
        ];
    }
}
