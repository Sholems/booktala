<?php

namespace App\Http\Requests\Staff;

use App\Models\StaffSchedule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for TeamController::storeShift(). Access is checked by the route middleware.
 */
class TeamStoreShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'site_id' => 'required|exists:sites,id',
            'staff_id' => 'required|exists:staff,id',
            'date' => 'required|date|after_or_equal:today',
            'shift_type' => 'required|in:'.implode(',', array_keys(StaffSchedule::SHIFT_TIMES)),
            'notes' => 'nullable|string|max:500',
        ];
    }
}
