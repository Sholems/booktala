<?php

namespace App\Http\Requests\Operations;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for SecurityController::storeCheckpoint(). Access is checked by the route middleware.
 */
class SecurityStoreCheckpointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'site_id' => 'required|exists:sites,id',
            'name' => 'required|string|max:100',
        ];
    }
}
