<?php

namespace App\Http\Requests\Customer;

use App\Models\Reservation;
use App\Models\RoomServiceOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for RoomServiceController::store(). Access is checked by the route middleware and the controller.
 */
class RoomServiceStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reservation_id' => ['required', Rule::in(Reservation::where('user_id', $this->user()->id)->where('booking_status', 'checked_in')->pluck('id')->all())],
            'order_type' => ['required', Rule::in(array_keys(RoomServiceOrder::orderTypes()))],
            'items' => 'required|array|min:1|max:20',
            'items.*.name' => 'required|string|max:100',
            'items.*.quantity' => 'required|integer|min:1|max:50',
            'items.*.price' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:folio,paystack,cash',
            'special_instructions' => 'nullable|string|max:1000',
            'pickup_location' => 'nullable|string|max:255',
            'pickup_time' => 'nullable|date',
            'flight_number' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'reservation_id.in' => 'Concierge service is only available for a stay you are checked in to.',
        ];
    }
}
