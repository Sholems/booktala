<?php

namespace App\Http\Requests\Api;

use App\Http\Controllers\Api\AdminController;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for AdminController::updateReservation(). Access is checked by the route middleware.
 */
class AdminUpdateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:'.AdminController::BOOKING_STATUSES,
            'reason' => 'nullable|string|max:500',
        ];
    }
}
