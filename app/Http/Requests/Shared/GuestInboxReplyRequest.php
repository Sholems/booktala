<?php

namespace App\Http\Requests\Shared;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for ManagesGuestInbox::reply(). Access is checked by the route middleware and the controller.
 */
class GuestInboxReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['message' => 'required|string|max:2000'];
    }
}
