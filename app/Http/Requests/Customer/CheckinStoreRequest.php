<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for CheckinController::store(). Access is checked by the route middleware and the controller.
 */
class CheckinStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'guest_first_name' => 'required|string|max:255',
            'guest_last_name' => 'required|string|max:255',
            'guest_email' => 'required|email|max:255',
            'guest_phone' => 'required|string|max:30',
            'arrival_flight' => 'nullable|string|max:50',
            'arrival_time' => 'nullable|string|max:20',
            'special_requests' => 'nullable|string|max:1000',
            'id_type' => 'required|in:passport,national_id,drivers_license,voters_card,other',
            'id_number' => 'required|string|max:255',
            'num_adults' => 'required|integer|min:1|max:'.max(1, (int) $this->route('reservation')->guests),
            'num_children' => 'nullable|integer|min:0|max:20',
            'vehicle_plate' => 'nullable|string|max:20',
            'emergency_contact_name' => 'required|string|max:255',
            'emergency_contact_phone' => 'required|string|max:30',
        ];
    }
}
