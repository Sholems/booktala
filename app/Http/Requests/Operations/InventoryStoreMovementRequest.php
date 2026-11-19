<?php

namespace App\Http\Requests\Operations;

use App\Models\StockMovement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for InventoryController::storeMovement(). Access is checked by the route middleware and the controller.
 */
class InventoryStoreMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_keys(StockMovement::TYPES))],
            'quantity' => 'required|numeric|min:0|max:1000000',
            'property_id' => ['nullable', Rule::exists('properties', 'id')->where('site_id', $this->route('item')->site_id)],
            'unit_cost' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:255',
        ];
    }
}
