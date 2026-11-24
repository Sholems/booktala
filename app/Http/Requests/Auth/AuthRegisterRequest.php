<?php

namespace App\Http\Requests\Auth;

use App\Rules\Turnstile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for AuthController::register(). Access is checked by the route middleware.
 */
class AuthRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'nullable|string',
            'password' => 'required|confirmed|min:8',
            'cf-turnstile-response' => [Rule::requiredIf(config('turnstile.enabled')), new Turnstile('register')],
        ];
    }
}
