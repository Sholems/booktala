<?php

return [
    'enabled' => filter_var(env('TURNSTILE_ENABLED', false), FILTER_VALIDATE_BOOL),
    'site_key' => env('TURNSTILE_SITE_KEY'),
    'secret' => env('TURNSTILE_SECRET'),
    'hostnames' => array_values(array_filter(array_map(
        static fn (string $hostname): string => strtolower(trim($hostname)),
        explode(',', (string) env('TURNSTILE_HOSTNAMES', 'localhost,127.0.0.1'))
    ))),
    'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    'timeout' => (int) env('TURNSTILE_TIMEOUT', 10),
];
