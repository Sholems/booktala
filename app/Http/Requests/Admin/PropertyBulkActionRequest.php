<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for PropertyController::bulkAction(). Access is checked by the route middleware.
 */
class PropertyBulkActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_ids' => 'required|array',
            'action' => 'required|string|in:active,inactive,featured,delete',
        ];
    }
}
