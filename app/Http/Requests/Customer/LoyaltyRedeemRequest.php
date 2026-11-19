<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for LoyaltyController::redeem(). Access is checked by the route middleware.
 */
class LoyaltyRedeemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'points' => 'required|integer|min:100',
        ];
    }
}
