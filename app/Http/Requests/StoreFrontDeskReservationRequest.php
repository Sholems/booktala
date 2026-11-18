<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFrontDeskReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reservation.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'site_id' => ['required', 'integer', 'exists:sites,id'],
            'unit_id' => ['required', 'integer', 'exists:properties,id'],
            'guest_id' => ['nullable', 'integer', 'exists:guests,id'],
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'guest_phone' => ['required', 'string', 'max:30'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', Rule::in(['cash', 'pos', 'transfer'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:100', Rule::requiredIf(fn () => in_array($this->payment_method, ['pos', 'transfer'], true))],
            'discount_code' => ['nullable', 'string'],
            'booking_source' => ['nullable', Rule::in(['walk_in', 'phone'])],
            'check_in_now' => ['nullable', 'boolean'],
        ];
    }
}
