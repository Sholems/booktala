<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for PayrollController::updateSettings(). Access is checked by the route middleware.
 */
class PayrollUpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bands' => 'required|array|min:1|max:10',
            'bands.*.width' => 'nullable|numeric|min:1',
            'bands.*.rate' => 'required|numeric|min:0|max:100',
            'rent_relief_rate' => 'required|numeric|min:0|max:100',
            'rent_relief_cap' => 'required|numeric|min:0',
            'pension_employee_rate' => 'required|numeric|min:0|max:100',
            'pension_employer_rate' => 'required|numeric|min:0|max:100',
            'nhf_rate' => 'required|numeric|min:0|max:100',
            'working_days_per_month' => 'required|integer|min:1|max:31',
        ];
    }
}
