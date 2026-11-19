<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for StaffController::addAssignment(). Access is checked by the route middleware.
 */
class StaffAddAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'site_id' => 'required|exists:sites,id',
            'position_id' => 'required|exists:positions,id',
            'starts_on' => 'nullable|date',
            'is_primary' => 'nullable|boolean',
        ];
    }
}
