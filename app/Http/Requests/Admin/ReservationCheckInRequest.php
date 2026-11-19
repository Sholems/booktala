<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for ReservationController::checkIn(). Access is checked by the route middleware.
 */
class ReservationCheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'allow_unpaid' => ['nullable', 'boolean'],
        ];
    }
}
