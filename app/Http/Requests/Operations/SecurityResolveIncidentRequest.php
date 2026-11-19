<?php

namespace App\Http\Requests\Operations;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for SecurityController::resolveIncident(). Access is checked by the route middleware.
 */
class SecurityResolveIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['resolution' => 'required|string|max:3000'];
    }
}
