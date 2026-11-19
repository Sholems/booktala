<?php

namespace App\Http\Requests\Operations;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for InventoryController::updateItem(). Access is checked by the route middleware.
 */
class InventoryUpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reorder_level' => 'required|numeric|min:0',
            'unit' => 'required|string|max:20',
        ];
    }
}
