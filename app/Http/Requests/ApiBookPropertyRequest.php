<?php

namespace App\Http\Requests;

use App\Models\Property;
use Illuminate\Foundation\Http\FormRequest;

class ApiBookPropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $maximumGuests = Property::query()->whereKey($this->route('id'))->value('max_guests') ?? 1;

        return [
            'check_in' => ['required', 'date', 'after:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests' => ['required', 'integer', 'min:1', 'max:'.$maximumGuests],
            'discount_code' => ['nullable', 'string'],
            'special_requests' => ['nullable', 'string', 'max:2000'],
            'payment_mode' => ['nullable', 'in:full,partial'],
        ];
    }
}
