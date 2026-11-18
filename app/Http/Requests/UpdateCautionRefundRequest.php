<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCautionRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reservation.refund') ?? false;
    }

    public function rules(): array
    {
        return [
            'caution_fee_status' => ['required', 'in:refunded,forfeited'],
        ];
    }
}
