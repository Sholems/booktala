<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendchampService
{
    protected ?string $publicKey;

    protected string $mode;

    protected string $smsSenderId;

    protected ?string $whatsappSender;

    protected string $route;

    protected string $baseUrl = 'https://api.sendchamp.com/v1';

    public function __construct()
    {
        $this->publicKey = config('services.sendchamp.public_key');
        $this->mode = config('services.sendchamp.mode', 'live');
        $this->smsSenderId = config('services.sendchamp.sms_sender_id', 'BookTala');
        $this->whatsappSender = config('services.sendchamp.whatsapp_phone_number_id');
        $this->route = config('services.sendchamp.route', 'dnd');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->publicKey);
    }

    /**
     * Send single or bulk SMS via Sendchamp
     *
     * @param  string|array  $to  Phone number(s)
     * @param  string  $message  Text content
     * @param  string|null  $senderName  Custom alphanumeric sender ID
     * @param  string|null  $route  Delivery route ('dnd', 'non_dnd', 'international')
     * @return array [success => bool, reference => string|null, response => array|string, error => string|null]
     */
    public function sendSms(string|array $to, string $message, ?string $senderName = null, ?string $route = null): array
    {
        if (! $this->isConfigured()) {
            Log::warning('Sendchamp public key not configured. SMS not sent.');

            return [
                'success' => false,
                'error' => 'Sendchamp public key not configured in .env',
            ];
        }

        $recipients = is_array($to) ? $to : [$to];
        $formattedRecipients = array_values(array_filter(array_map([$this, 'formatPhone'], $recipients)));

        if (empty($formattedRecipients)) {
            return [
                'success' => false,
                'error' => 'No valid recipient phone numbers provided',
            ];
        }

        $payload = [
            'to' => $formattedRecipients,
            'message' => $message,
            'sender_name' => $senderName ?: $this->smsSenderId,
            'route' => $route ?: $this->route,
        ];

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(12)
                ->post("{$this->baseUrl}/sms/send", $payload);

            $data = $response->json();

            if ($response->successful() && ($data['status'] ?? '') === 'success') {
                $ref = $data['data']['reference'] ?? ($data['data']['id'] ?? 'SC-'.uniqid());

                return [
                    'success' => true,
                    'reference' => $ref,
                    'data' => $data,
                ];
            }

            $errorMessage = $data['message'] ?? $response->body();
            Log::error("Sendchamp SMS failed: {$errorMessage}");

            return [
                'success' => false,
                'error' => $errorMessage,
                'data' => $data,
            ];
        } catch (\Throwable $e) {
            Log::error('Sendchamp SMS exception: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send WhatsApp Message via Sendchamp
     *
     * @return array [success => bool, reference => string|null, error => string|null]
     */
    public function sendWhatsApp(string $recipientPhone, string $message, ?string $senderId = null): array
    {
        if (! $this->isConfigured()) {
            Log::warning('Sendchamp public key not configured. WhatsApp not sent.');

            return [
                'success' => false,
                'error' => 'Sendchamp public key not configured in .env',
            ];
        }

        $formattedPhone = $this->formatPhone($recipientPhone);
        $sender = $senderId ?: $this->whatsappSender;

        $payload = [
            'recipient' => $formattedPhone,
            'message' => $message,
            'type' => 'text',
        ];

        if (! empty($sender)) {
            $payload['sender'] = $sender;
        }

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(12)
                ->post("{$this->baseUrl}/whatsapp/message/send", $payload);

            $data = $response->json();

            if ($response->successful() && ($data['status'] ?? '') === 'success') {
                $ref = $data['data']['reference'] ?? ($data['data']['id'] ?? 'WA-SC-'.uniqid());

                return [
                    'success' => true,
                    'reference' => $ref,
                    'data' => $data,
                ];
            }

            $errorMessage = $data['message'] ?? $response->body();
            Log::error("Sendchamp WhatsApp failed: {$errorMessage}");

            return [
                'success' => false,
                'error' => $errorMessage,
                'data' => $data,
            ];
        } catch (\Throwable $e) {
            Log::error('Sendchamp WhatsApp exception: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send OTP verification token via SMS or WhatsApp
     *
     * @param  string  $channel  'sms' or 'whatsapp'
     * @param  int  $tokenLength  4-8 digits
     * @param  int  $expiryMinutes  Expiration duration
     * @return array [success => bool, reference => string|null, error => string|null]
     */
    public function sendOtp(string $phone, string $channel = 'sms', int $tokenLength = 6, int $expiryMinutes = 10): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Sendchamp public key not configured',
            ];
        }

        $formattedPhone = $this->formatPhone($phone);

        $payload = [
            'channel' => strtolower($channel),
            'sender' => $channel === 'whatsapp' ? ($this->whatsappSender ?: $this->smsSenderId) : $this->smsSenderId,
            'token_type' => 'numeric',
            'token_length' => $tokenLength,
            'expiration_time' => $expiryMinutes,
            'customer_mobile_number' => $formattedPhone,
        ];

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(12)
                ->post("{$this->baseUrl}/verification/create", $payload);

            $data = $response->json();

            if ($response->successful() && ($data['status'] ?? '') === 'success') {
                return [
                    'success' => true,
                    'reference' => $data['data']['reference'] ?? null,
                    'data' => $data['data'] ?? [],
                ];
            }

            return [
                'success' => false,
                'error' => $data['message'] ?? 'Unable to generate verification OTP',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify/Confirm an OTP code against a reference
     *
     * @param  string  $reference  Verification reference returned by sendOtp
     * @param  string  $code  User-entered OTP digits
     * @return array [success => bool, message => string, error => string|null]
     */
    public function verifyOtp(string $reference, string $code): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Sendchamp public key not configured',
            ];
        }

        $payload = [
            'verification_reference' => trim($reference),
            'verification_code' => trim($code),
        ];

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(10)
                ->post("{$this->baseUrl}/verification/confirm", $payload);

            $data = $response->json();

            if ($response->successful() && ($data['status'] ?? '') === 'success') {
                return [
                    'success' => true,
                    'message' => $data['message'] ?? 'Verification successful',
                ];
            }

            return [
                'success' => false,
                'error' => $data['message'] ?? 'Invalid or expired verification code',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Query live Sendchamp wallet balance
     */
    public function getWalletBalance(): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(8)
                ->get("{$this->baseUrl}/wallet/wallet_balance");

            if ($response->successful()) {
                return $response->json('data');
            }
        } catch (\Throwable $e) {
            Log::warning('Sendchamp getWalletBalance failed: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Format raw phone numbers for Sendchamp
     * Sendchamp expects country code without '+' (e.g. 2349116110242)
     */
    public function formatPhone(string $phone): string
    {
        // Strip everything except numbers
        $digits = preg_replace('/[^0-9]/', '', $phone);

        // If local Nigerian number starting with 0 (e.g. 09116110242), convert to 2349116110242
        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return '234'.substr($digits, 1);
        }

        // If 10 digits without leading 0 (e.g. 9116110242), prefix 234
        if (strlen($digits) === 10) {
            return '234'.$digits;
        }

        return $digits;
    }

    protected function getHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$this->publicKey,
        ];
    }
}
