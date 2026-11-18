<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservationRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reservation.refund') ?? false;
    }

    public function rules(): array
    {
        return [
            'refund_status' => ['required', 'in:refunded,declined'],
        ];
    }
}
