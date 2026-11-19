<?php

namespace App\Http\Requests\Admin;

use App\Models\StaffSchedule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for StaffScheduleController::store(). Access is checked by the route middleware.
 */
class StaffScheduleStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'staff_id' => 'required|exists:staff,id',
            'site_id' => 'nullable|exists:sites,id',
            'date' => 'required|date',
            'shift_type' => 'required|in:'.implode(',', array_keys(StaffSchedule::SHIFT_TIMES)),
            'notes' => 'nullable|string|max:500',
        ];
    }
}
