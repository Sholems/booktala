<?php

namespace App\Http\Requests;

use App\Rules\Turnstile;
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
            'policy_agreement' => ['required', 'accepted'],
            'guest_name' => [Rule::requiredIf($isGuest), 'nullable', 'string', 'max:255'],
            'guest_email' => [Rule::requiredIf($isGuest), 'nullable', 'email'],
            'guest_phone' => [Rule::requiredIf($isGuest), 'nullable', 'string', 'max:30'],
            'guest_password' => [Rule::requiredIf($isGuest), 'nullable', 'string'],
            'cf-turnstile-response' => [Rule::requiredIf(config('turnstile.enabled')), new Turnstile('booking_process')],
        ];
    }

    public function messages(): array
    {
        return [
            'policy_agreement.required' => 'You must review and agree to the House Rules, Cancellation Policy, and Terms of Service to proceed.',
            'policy_agreement.accepted' => 'You must accept the House Rules, Cancellation Policy, and Terms of Service to complete your reservation.',
        ];
    }
}
