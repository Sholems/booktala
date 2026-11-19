<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for StaffScheduleController::updateStatus(). Access is checked by the route middleware.
 */
class StaffScheduleUpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:scheduled,completed,cancelled,no_show',
        ];
    }
}
