<?php

namespace App\Http\Requests\Admin;

use App\Models\Owner;
use App\Models\Property;
use App\Models\Site;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for PropertyController::store(). Access is checked by the route middleware.
 */
class PropertyStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required',
            'description' => 'required',
            'address' => 'required',
            'city' => 'required',
            'state' => 'required',
            'price_per_night' => 'required|numeric',
            'price_per_week' => 'nullable|numeric',
            'price_per_month' => 'nullable|numeric',
            'max_guests' => 'required|integer',
            'bedrooms' => 'required|integer',
            'bathrooms' => 'required|integer',
            'toilets' => 'required|integer',
            'min_nights' => 'nullable|integer|min:1',
            'max_nights' => 'nullable|integer|min:1',
            'status' => 'nullable|in:active,inactive,maintenance',
            'is_featured' => 'nullable|boolean',
            'short_description' => 'nullable|string',
            'house_rules' => 'nullable|string',
            'cancellation_policy' => 'nullable|string',
            // Blank owner = the company; blank site = its own one-unit site (see Property::booted).
            'owner_id' => 'nullable|exists:owners,id',
            'site_id' => 'nullable|exists:sites,id',
        ];
    }
}
