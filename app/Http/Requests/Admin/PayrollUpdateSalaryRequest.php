<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for PayrollController::updateSalary(). Access is checked by the route middleware.
 */
class PayrollUpdateSalaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'basic' => 'required|numeric|min:0',
            'housing' => 'nullable|numeric|min:0',
            'transport' => 'nullable|numeric|min:0',
            'other_allowance' => 'nullable|numeric|min:0',
            'overtime_rate' => 'nullable|numeric|min:0',
            'annual_rent' => 'nullable|numeric|min:0',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_name' => 'nullable|string|max:150',
            'bank_account_number' => ['nullable', 'regex:/^\d{10}$/'],
            'tax_id' => 'nullable|string|max:30',
            'pension_pin' => 'nullable|string|max:30',
        ];
    }

    public function messages(): array
    {
        return ['bank_account_number.regex' => 'Enter the 10-digit NUBAN account number.'];
    }
}
