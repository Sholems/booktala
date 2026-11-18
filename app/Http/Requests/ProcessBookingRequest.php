<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isGuest = ! $this->user();

        return [
            'property_id' => ['required', 'exists:properties,id'],
            'check_in' => ['required', 'date', 'after:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests' => ['required', 'integer', 'min:1'],
            'discount_code' => ['nullable', 'string'],
            'special_requests' => ['nullable', 'string', 'max:2000'],
            'payment_mode' => ['nullable', 'in:full,partial'],
            'guest_name' => [Rule::requiredIf($isGuest), 'nullable', 'string', 'max:255'],
            'guest_email' => [Rule::requiredIf($isGuest), 'nullable', 'email'],
            'guest_phone' => [Rule::requiredIf($isGuest), 'nullable', 'string', 'max:30'],
            'guest_password' => [Rule::requiredIf($isGuest), 'nullable', 'string'],
        ];
    }
}
