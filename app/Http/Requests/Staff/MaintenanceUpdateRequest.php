<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for MaintenanceController::update(). Access is checked by the route middleware.
 */
class MaintenanceUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:in_progress,resolved',
            'resolution_notes' => 'nullable|string|max:2000|required_if:status,resolved',
        ];
    }

    public function messages(): array
    {
        return [
            'resolution_notes.required_if' => 'Describe what you fixed before marking it resolved.',
        ];
    }
}
