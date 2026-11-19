<?php

namespace App\Http\Requests\Admin;

use App\Models\Reservation;
use App\Support\PropertyAccess;
use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reservationId = $this->input('reservation_id');
        if ($reservationId) {
            $reservation = Reservation::find($reservationId);
            if ($reservation && $reservation->property_id) {
                return $this->user() && PropertyAccess::canAccess($this->user(), (int) $reservation->property_id);
            }
        }

        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'reservation_id' => ['required', 'exists:reservations,id'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reservation_id.required' => 'A valid reservation must be selected to generate an invoice.',
            'reservation_id.exists' => 'The selected reservation could not be found.',
        ];
    }
}
