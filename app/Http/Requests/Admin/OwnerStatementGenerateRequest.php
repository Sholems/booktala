<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for OwnerStatementController::generate(). Access is checked by the route middleware.
 */
class OwnerStatementGenerateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'month' => 'required|date_format:Y-m',
            'owner_id' => 'nullable|exists:owners,id',
        ];
    }
}
