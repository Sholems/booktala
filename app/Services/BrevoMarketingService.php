<?php

namespace App\Services;

use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrevoMarketingService
{
    protected ?string $apiKey;

    protected string $baseUrl = 'https://api.brevo.com/v3';

    public function __construct()
    {
        $this->apiKey = config('services.brevo.api_key');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey) && str_starts_with($this->apiKey, 'xkeysib-');
    }

    /**
     * Create or update a contact in Brevo
     */
    public function syncContact(string $email, array $attributes = [], array $listIds = []): bool
    {
        if (empty($this->apiKey)) {
            return false;
        }

        $payload = [
            'email' => strtolower(trim($email)),
            'updateEnabled' => true,
        ];

        if (! empty($attributes)) {
            $payload['attributes'] = $attributes;
        }

        $defaultListId = config('services.brevo.list_id');
        if (! empty($listIds)) {
            $payload['listIds'] = array_map('intval', $listIds);
        } elseif (! empty($defaultListId)) {
            $payload['listIds'] = [(int) $defaultListId];
        }

        try {
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'api-key' => $this->apiKey,
                'content-type' => 'application/json',
            ])->timeout(10)->post("{$this->baseUrl}/contacts", $payload);

            if ($response->successful() || $response->status() === 204) {
                return true;
            }

            Log::warning("Brevo contact sync responded with status {$response->status()}: ".$response->body());

            return false;
        } catch (\Throwable $e) {
            Log::warning('Brevo contact sync request failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Sync a newsletter subscriber model to Brevo
     */
    public function createOrUpdateSubscriber(NewsletterSubscriber $subscriber): bool
    {
        $attributes = [
            'FIRSTNAME' => $subscriber->name ?: 'VIP Member',
            'USER_TYPE' => 'newsletter_subscriber',
            'SUBSCRIBED_AT' => $subscriber->subscribed_at ? $subscriber->subscribed_at->toIso8601String() : now()->toIso8601String(),
        ];

        return $this->syncContact($subscriber->email, $attributes);
    }

    /**
     * Sync a registered user / customer / affiliate to Brevo
     */
    public function syncUser(User $user): bool
    {
        $attributes = [
            'FIRSTNAME' => $user->name ?: '',
            'PHONE' => $user->phone ?: '',
            'USER_TYPE' => $user->getRoleNames()->first() ?? 'customer',
        ];

        if ($user->affiliate) {
            $attributes['AFFILIATE_CODE'] = $user->affiliate->partner_code;
            $attributes['AFFILIATE_STATUS'] = $user->affiliate->status;
        }

        return $this->syncContact($user->email, $attributes);
    }

    /**
     * Fetch Brevo account details (for verification)
     */
    public function getAccountInfo(): ?array
    {
        if (empty($this->apiKey)) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'api-key' => $this->apiKey,
            ])->timeout(5)->get("{$this->baseUrl}/account");

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Throwable $e) {
            Log::warning('Brevo getAccountInfo failed: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Fetch contact lists from Brevo
     */
    public function getLists(): array
    {
        if (empty($this->apiKey)) {
            return [];
        }

        try {
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'api-key' => $this->apiKey,
            ])->timeout(5)->get("{$this->baseUrl}/contacts/lists?limit=20");

            if ($response->successful()) {
                return $response->json('lists') ?? [];
            }
        } catch (\Throwable $e) {
        }

        return [];
    }
}
