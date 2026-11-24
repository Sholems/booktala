<?php

namespace App\Mail;

use App\Models\MarketingCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class MarketingCampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MarketingCampaign $campaign,
        public array $recipient,
        public ?Collection $properties = null
    ) {
        $this->properties = $properties ?? $campaign->getFeaturedProperties();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->campaign->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.marketing.campaign',
            with: [
                'campaign' => $this->campaign,
                'recipient' => $this->recipient,
                'properties' => $this->properties,
                'preview_text' => $this->campaign->preview_text,
                'unsubscribe_url' => $this->recipient['unsubscribe_url'] ?? route('newsletter.unsubscribe', ['token' => 'guest']),
            ]
        );
    }
}
