<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for ProfileController::connectGoogle(). Access is checked by the route middleware.
 */
class ProfileConnectGoogleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['google_id' => 'required'];
    }
}
