<?php

namespace App\Http\Requests\Admin;

use App\Models\StaffDeduction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for PayrollController::addDeduction(). Access is checked by the route middleware.
 */
class PayrollAddDeductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_keys(StaffDeduction::TYPES))],
            'description' => 'required|string|max:255',
            'monthly_amount' => 'required|numeric|min:1',
            'balance' => 'nullable|numeric|min:1',
        ];
    }
}
