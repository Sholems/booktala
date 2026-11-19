<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Filters for the attendance report and its CSV export.
 */
class AttendanceFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'site_id' => 'nullable|exists:sites,id',
            'staff_id' => 'nullable|exists:staff,id',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ];
    }
}
