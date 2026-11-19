<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for MessageController::store(). Access is checked by the route middleware.
 */
class MessageStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => 'required|string|max:2000',
            'property_id' => 'nullable|exists:properties,id',
            'reservation_id' => ['nullable', Rule::exists('reservations', 'id')->where('user_id', auth()->id())],
        ];
    }
}
