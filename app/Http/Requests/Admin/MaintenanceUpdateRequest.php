<?php

namespace App\Http\Requests\Admin;

use App\Rules\WorksAtProperty;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for MaintenanceController::update(). Access is checked by the route middleware and the controller.
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
            'status' => 'required|in:pending,in_progress,resolved',
            'assigned_to' => ['nullable', new WorksAtProperty($this->route('maintenanceReport')->property_id)],
            'resolution_notes' => 'nullable|string|max:2000|required_if:status,resolved',
        ];
    }

    public function messages(): array
    {
        return [
            'resolution_notes.required_if' => 'Add a note describing the fix before marking it resolved.',
        ];
    }
}
