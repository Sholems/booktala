<?php

namespace App\Jobs;

use App\Mail\MarketingCampaignMail;
use App\Models\CampaignLog;
use App\Models\MarketingCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DispatchCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes timeout for batches

    public function __construct(
        public MarketingCampaign $campaign
    ) {}

    public function handle(): void
    {
        $this->campaign->update([
            'status' => 'sending',
        ]);

        $recipients = $this->campaign->resolveRecipients();
        $properties = $this->campaign->getFeaturedProperties();

        $this->campaign->update([
            'recipient_count' => $recipients->count(),
            'sent_count' => 0,
            'failed_count' => 0,
        ]);

        $sentCount = 0;
        $failedCount = 0;

        foreach ($recipients as $recipient) {
            $email = $recipient['email'];
            $name = $recipient['name'] ?? null;
            $type = $recipient['type'] ?? 'subscriber';

            try {
                Mail::to($email)->send(new MarketingCampaignMail($this->campaign, $recipient, $properties));

                CampaignLog::create([
                    'marketing_campaign_id' => $this->campaign->id,
                    'recipient_email' => $email,
                    'recipient_name' => $name,
                    'recipient_type' => $type,
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);

                $sentCount++;
            } catch (\Throwable $e) {
                Log::error("Failed sending campaign #{$this->campaign->id} to {$email}: " . $e->getMessage());

                CampaignLog::create([
                    'marketing_campaign_id' => $this->campaign->id,
                    'recipient_email' => $email,
                    'recipient_name' => $name,
                    'recipient_type' => $type,
                    'status' => 'failed',
                    'error_message' => substr($e->getMessage(), 0, 500),
                ]);

                $failedCount++;
            }

            // Brief delay to be polite to relay limits
            usleep(100000); // 0.1s
        }

        $this->campaign->update([
            'status' => 'sent',
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'sent_at' => now(),
        ]);
    }
}
