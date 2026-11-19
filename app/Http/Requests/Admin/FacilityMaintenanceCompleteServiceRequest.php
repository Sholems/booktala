<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for FacilityMaintenanceController::completeService(). Access is checked by the route middleware.
 */
class FacilityMaintenanceCompleteServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_notes' => 'nullable|string|max:1000',
            'actual_cost' => 'nullable|numeric|min:0',
        ];
    }
}
