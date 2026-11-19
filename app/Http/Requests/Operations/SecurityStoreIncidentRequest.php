<?php

namespace App\Http\Requests\Operations;

use App\Models\Incident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for SecurityController::storeIncident(). Access is checked by the route middleware.
 */
class SecurityStoreIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'site_id' => 'required|exists:sites,id',
            'property_id' => 'nullable|exists:properties,id',
            'occurred_at' => 'required|date|before_or_equal:now',
            'category' => ['required', Rule::in(array_keys(Incident::CATEGORIES))],
            'severity' => ['required', Rule::in(array_keys(Incident::SEVERITIES))],
            'title' => 'required|string|max:150',
            'description' => 'required|string|max:5000',
            'actions_taken' => 'nullable|string|max:3000',
            'photo' => 'nullable|image|max:8192',
        ];
    }
}
