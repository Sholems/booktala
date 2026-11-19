<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Validation for OwnerController::portalAccess(). Access is checked by the route middleware and the controller.
 */
class OwnerPortalAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('owner')->user_id)],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }
}
