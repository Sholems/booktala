<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Validation for StaffController::store(). Access is checked by the route middleware.
 */
class StaffStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'phone' => 'nullable|string|max:30',
            'position_id' => 'required|exists:positions,id',
            'site_id' => 'required|exists:sites,id',
            'password' => ['required', Password::min(8)],
        ];
    }
}
