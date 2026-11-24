<?php

namespace App\Http\Requests\Public;

use App\Rules\Turnstile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for PageController::submitContact(). Access is checked by the route middleware.
 */
class PageSubmitContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'subject' => ['required', 'string', 'max:190'],
            'message' => ['required', 'string', 'max:3000'],
            'cf-turnstile-response' => [Rule::requiredIf(config('turnstile.enabled')), new Turnstile('contact_submit')],
        ];
    }
}
