<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for HousekeepingInspectionController::reject(). Access is checked by the route middleware.
 */
class HousekeepingInspectionRejectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => 'required|string|max:1000',
        ];
    }
}
