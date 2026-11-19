<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for MessageController::startConversation(). Access is checked by the route middleware.
 */
class MessageStartConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'property_id' => 'nullable|exists:properties,id',
            'message' => 'required|string',
        ];
    }
}
