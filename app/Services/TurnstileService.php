<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class TurnstileService
{
    public function verify(string $token, string $action, ?string $remoteIp = null): bool
    {
        if (! config('turnstile.enabled')) {
            return true;
        }

        $secret = (string) config('turnstile.secret');

        if ($secret === '' || $token === '') {
            return false;
        }

        $payload = [
            'secret' => $secret,
            'response' => $token,
        ];

        if ($remoteIp && filter_var($remoteIp, FILTER_VALIDATE_IP)) {
            $payload['remoteip'] = $remoteIp;
        }

        try {
            $response = Http::asForm()
                ->timeout((int) config('turnstile.timeout', 10))
                ->post((string) config('turnstile.verify_url'), $payload);
        } catch (Throwable) {
            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        $result = $response->json();
        $hostname = strtolower((string) ($result['hostname'] ?? ''));
        $allowedHostnames = config('turnstile.hostnames', []);

        return ($result['success'] ?? false) === true
            && hash_equals($action, (string) ($result['action'] ?? ''))
            && in_array($hostname, $allowedHostnames, true);
    }
}
