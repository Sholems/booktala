<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for StaffController::update(). Access is checked by the route middleware and the controller.
 */
class StaffUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->route('user')->id)],
            'phone' => 'nullable|string|max:30',
            'status' => 'required|in:active,suspended',
        ];
    }
}
