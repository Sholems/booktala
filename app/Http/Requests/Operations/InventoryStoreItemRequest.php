<?php

namespace App\Http\Requests\Operations;

use App\Models\InventoryItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for InventoryController::storeItem(). Access is checked by the route middleware.
 */
class InventoryStoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'site_id' => 'required|exists:sites,id',
            'name' => ['required', 'string', 'max:100', Rule::unique('inventory_items')->where('site_id', $this->integer('site_id'))],
            'category' => ['required', Rule::in(array_keys(InventoryItem::CATEGORIES))],
            'unit' => 'required|string|max:20',
            'reorder_level' => 'nullable|numeric|min:0',
            'opening_stock' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return ['name.unique' => 'This site already has an item with that name.'];
    }
}
