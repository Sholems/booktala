<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for EventBookingController::store(). Access is checked by the route middleware.
 */
class EventBookingStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_space_id' => 'required|exists:event_spaces,id',
            'event_name' => 'required|string|max:255',
            'event_type' => 'required|in:wedding,birthday,corporate,conference,seminar,reunion,other',
            'event_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'num_guests' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ];
    }
}
