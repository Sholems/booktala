<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for RoomTypeController::updateRoomStatus(). Access is checked by the route middleware.
 */
class RoomTypeUpdateRoomStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:available,occupied,reserved,maintenance,cleaning',
        ];
    }
}
