<?php

namespace App\Http\Requests\Admin;

use App\Rules\WorksAtProperty;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for TaskController::update(). Access is checked by the route middleware and the controller.
 */
class TaskUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assigned_to' => ['nullable', new WorksAtProperty($this->route('task')->property_id)],
            'status' => 'sometimes|in:pending,in_progress,completed',
        ];
    }
}
