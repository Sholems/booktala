<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for RoomTypeController::update(). Access is checked by the route middleware.
 */
class RoomTypeUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'base_price' => 'required|numeric|min:0',
            'total_rooms' => 'required|integer|min:1',
            'max_adults' => 'required|integer|min:1',
        ];
    }
}
