<?php

namespace App\Http\Requests\Admin;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for ExpenseController::store(). Access is checked by the route middleware.
 */
class ExpenseStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_id' => 'required|exists:properties,id',
            'category' => ['required', Rule::in(array_keys(Expense::CATEGORIES))],
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01|max:100000000',
            'spent_on' => 'required|date|before_or_equal:today',
            'charge_to' => 'required|in:owner,company',
            'maintenance_report_id' => 'nullable|exists:maintenance_reports,id',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }
}
