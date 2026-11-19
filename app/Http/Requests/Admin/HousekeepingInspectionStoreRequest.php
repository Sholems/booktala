<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for HousekeepingInspectionController::store(). Access is checked by the route middleware.
 */
class HousekeepingInspectionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'unit_id' => 'required|exists:properties,id',
            'cleaner_id' => 'required|exists:users,id',
            'task_id' => 'nullable|exists:tasks,id',
            'cleaner_notes' => 'nullable|string|max:1000',
            'checklist' => 'nullable|array',
            'before_photos' => 'nullable|array',
            'after_photos' => 'nullable|array',
            'before_images.*' => 'nullable|image|max:8192',
            'after_images.*' => 'nullable|image|max:8192',
        ];
    }
}
