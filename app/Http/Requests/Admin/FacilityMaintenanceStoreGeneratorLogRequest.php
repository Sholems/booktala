<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for FacilityMaintenanceController::storeGeneratorLog(). Access is checked by the route middleware.
 */
class FacilityMaintenanceStoreGeneratorLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'site_id' => 'required|exists:sites,id',
            'generator_name' => 'required|string|max:100',
            'run_hours_start' => 'required|numeric|min:0',
            'run_hours_end' => 'required|numeric|gte:run_hours_start',
            'litres_added' => 'required|numeric|min:0',
            'diesel_cost' => 'required|numeric|min:0',
            'fuel_level_before_percent' => 'nullable|integer|between:0,100',
            'fuel_level_after_percent' => 'nullable|integer|between:0,100',
            'logged_at' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
