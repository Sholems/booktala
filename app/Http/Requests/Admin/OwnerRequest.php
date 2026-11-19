<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Creating or editing a property owner and their management agreement.
 */
class OwnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:company,individual',
            'tax_id' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            // Management agreement and payout account.
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_name' => 'nullable|string|max:150',
            'bank_account_number' => ['nullable', 'regex:/^\d{10}$/'],
        ];
    }

    public function messages(): array
    {
        return ['bank_account_number.regex' => 'Enter the 10-digit NUBAN account number.'];
    }
}
