<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for InvoiceController::updateStatus(). Access is checked by the route middleware.
 */
class InvoiceUpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:draft,issued,partial,paid,overdue,cancelled',
        ];
    }
}
