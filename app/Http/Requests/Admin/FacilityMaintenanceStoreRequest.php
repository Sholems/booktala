<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for FacilityMaintenanceController::store(). Access is checked by the route middleware.
 */
class FacilityMaintenanceStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'site_id' => 'required|exists:sites,id',
            'property_id' => 'nullable|exists:properties,id',
            'equipment_type' => 'required|in:generator,ac,plumbing,fumigation,inverter,borehole',
            'title' => 'required|string|max:255',
            'frequency_days' => 'required|integer|min:1|max:365',
            'next_due_on' => 'required|date',
            'vendor_or_staff' => 'nullable|string|max:255',
            'estimated_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
