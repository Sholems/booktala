<?php

namespace App\Http\Requests\Operations;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for SecurityController::updatePatrolSettings(). Access is checked by the route middleware.
 */
class SecurityUpdatePatrolSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patrol_interval_minutes' => 'nullable|integer|min:15|max:720',
            'patrol_from' => 'nullable|date_format:H:i|required_with:patrol_until',
            'patrol_until' => 'nullable|date_format:H:i|required_with:patrol_from',
        ];
    }
}
