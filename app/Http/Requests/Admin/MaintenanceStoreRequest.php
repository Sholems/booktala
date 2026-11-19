<?php

namespace App\Http\Requests\Admin;

use App\Rules\WorksAtProperty;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for MaintenanceController::store(). Access is checked by the route middleware.
 */
class MaintenanceStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'property_id' => 'required|exists:properties,id',
            'description' => 'required|string|max:2000',
            'priority' => 'nullable|in:low,medium,high,urgent,emergency',
            'assigned_to' => ['nullable', new WorksAtProperty($this->integer('property_id') ?: null)],
        ];
    }
}
