<?php

namespace App\Http\Requests\Admin;

use App\Models\Position;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Creating or editing a staff position.
 */
class PositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('positions')->ignore($this->route('position')?->id)],
            'department' => ['required', Rule::in(array_keys(Position::DEPARTMENTS))],
            'level' => ['required', Rule::in(array_keys(Position::LEVELS))],
            'description' => 'nullable|string|max:255',
        ];
    }
}
