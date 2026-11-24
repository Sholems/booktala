<?php

namespace App\Http\Requests\Auth;

use App\Rules\Turnstile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for AuthController::login(). Access is checked by the route middleware.
 */
class AuthLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required',
            'cf-turnstile-response' => [Rule::requiredIf(config('turnstile.enabled')), new Turnstile('login')],
        ];
    }
}
