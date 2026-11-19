<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for TaskController::addNote(). Access is checked by the route middleware.
 */
class TaskAddNoteRequest extends FormRequest
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
