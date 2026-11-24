<?php

namespace App\Rules;

use App\Services\TurnstileService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Turnstile implements ValidationRule
{
    public function __construct(private readonly string $action) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! config('turnstile.enabled')) {
            return;
        }

        $verified = app(TurnstileService::class)->verify(
            is_string($value) ? $value : '',
            $this->action,
            request()->ip()
        );

        if (! $verified) {
            $fail('Security verification failed or expired. Please try again.');
        }
    }
}
