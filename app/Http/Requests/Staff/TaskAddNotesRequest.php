<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for TaskController::addNotes(). Access is checked by the route middleware.
 */
class TaskAddNotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['notes' => 'required|string|max:1000'];
    }
}
