<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterWelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public NewsletterSubscriber $subscriber
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to BookTala VIP Club · Claim 10% Off Your First Stay',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.marketing.welcome-vip',
            with: [
                'subscriber' => $this->subscriber,
                'preview_text' => 'Your 10% welcome discount voucher (WELCOME10) is inside. Experience luxury shortlets with guaranteed 24/7 power.',
                'unsubscribe_url' => $this->subscriber->unsubscribe_url,
            ]
        );
    }
}
