<?php

namespace App\Http\Requests\Public;

use App\Rules\Turnstile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for NewsletterController::subscribe(). Access is checked by the route middleware.
 */
class NewsletterSubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:190'],
            'name' => ['nullable', 'string', 'max:120'],
            'cf-turnstile-response' => [Rule::requiredIf(config('turnstile.enabled')), new Turnstile('newsletter_subscribe')],
        ];
    }
}
