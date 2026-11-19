<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for ProfileController::uploadId(). Access is checked by the route middleware.
 */
class ProfileUploadIdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }
}
