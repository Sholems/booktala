<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for OwnerStatementController::markPaid(). Access is checked by the route middleware.
 */
class OwnerStatementMarkPaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['payment_reference' => 'nullable|string|max:100'];
    }
}
