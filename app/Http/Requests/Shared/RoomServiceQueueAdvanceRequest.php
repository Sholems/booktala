<?php

namespace App\Http\Requests\Shared;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for ManagesRoomServiceQueue::advance(). Access is checked by the route middleware and the controller.
 */
class RoomServiceQueueAdvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'charge' => 'nullable|numeric|min:0|max:10000000',
        ];
    }
}
