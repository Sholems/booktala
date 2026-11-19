<?php

namespace App\Http\Requests\Admin;

use App\Rules\WorksAtProperty;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for TaskController::store(). Access is checked by the route middleware.
 */
class TaskStoreRequest extends FormRequest
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
            'assigned_to' => ['nullable', new WorksAtProperty($this->integer('property_id') ?: null)],
            'priority' => 'nullable|in:low,medium,high,urgent',
            'type' => 'required|in:cleaning,maintenance,inspection,checkin_prep',
            'due_date' => 'nullable|date',
            'description' => 'nullable|string|max:2000',
        ];
    }
}
