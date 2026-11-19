<?php

namespace App\Http\Requests\Staff;

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
            'checklist' => 'required|array',
            'cleaner_notes' => 'nullable|string|max:1000',
            'before_images.*' => 'nullable|image|max:8192',
            'after_images.*' => 'nullable|image|max:8192',
            'before_urls' => 'nullable|string',
            'after_urls' => 'nullable|string',
        ];
    }
}
