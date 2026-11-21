<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PostStayReviewMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Reservation $reservation
    ) {}

    public function envelope(): Envelope
    {
        $propertyName = $this->reservation->property->name ?? 'BookTala';
        return new Envelope(
            subject: "How was your stay at {$propertyName}? · BookTala Concierge",
        );
    }

    public function content(): Content
    {
        $guestEmail = $this->reservation->guest?->email ?: $this->reservation->user?->email;
        return new Content(
            view: 'emails.marketing.post-stay-review',
            with: [
                'reservation' => $this->reservation,
                'preview_text' => 'Share your thoughts on your recent stay and receive an exclusive ₦10,000 credit for your next visit.',
                'unsubscribe_url' => route('newsletter.unsubscribe', ['token' => md5(strtolower($guestEmail ?? 'guest'))]),
            ]
        );
    }
}
