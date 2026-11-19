<?php

namespace App\Http\Requests\Operations;

use App\Models\VisitorLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for SecurityController::storeVisitor(). Access is checked by the route middleware.
 */
class SecurityStoreVisitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'site_id' => 'required|exists:sites,id',
            'visitor_name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:30',
            'purpose' => ['required', Rule::in(array_keys(VisitorLog::PURPOSES))],
            'property_id' => 'nullable|exists:properties,id',
            'host' => 'nullable|string|max:150',
            'vehicle_plate' => 'nullable|string|max:20',
            'id_type' => ['nullable', Rule::in(array_keys(VisitorLog::ID_TYPES))],
            'notes' => 'nullable|string|max:500',
        ];
    }
}
