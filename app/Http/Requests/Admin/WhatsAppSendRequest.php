<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for WhatsAppController::send(). Access is checked by the route middleware.
 */
class WhatsAppSendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient_phone' => 'nullable|string|max:30',
            'phone' => 'nullable|string|max:30',
            'recipient_name' => 'nullable|string|max:100',
            'name' => 'nullable|string|max:100',
            'message' => 'required|string|max:2000',
            'reservation_id' => 'nullable|exists:reservations,id',
        ];
    }
}
