<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for AvailabilityController::check(). Access is checked by the route middleware.
 */
class AvailabilityCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'property_id' => 'nullable|exists:properties,id',
            'guests' => 'nullable|integer|min:1',
            'discount_code' => 'nullable|string',
        ];
    }
}
