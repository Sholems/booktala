<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Creating (POST: core fields required) or updating (only fields sent) a
 * property through the admin API.
 */
class AdminPropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'title' => "{$required}|string|max:255",
            'description' => "{$required}|string",
            'short_description' => 'nullable|string|max:255',
            'address' => "{$required}|string|max:255",
            'city' => "{$required}|string|max:100",
            'state' => "{$required}|string|max:100",
            'country' => 'sometimes|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'price_per_night' => "{$required}|numeric|min:0",
            'weekend_price' => 'nullable|numeric|min:0',
            'price_per_week' => 'nullable|numeric|min:0',
            'price_per_month' => 'nullable|numeric|min:0',
            'min_nights' => 'sometimes|integer|min:1',
            'max_nights' => 'sometimes|integer|min:1',
            'max_guests' => "{$required}|integer|min:1",
            'bedrooms' => "{$required}|integer|min:0",
            'bathrooms' => "{$required}|integer|min:0",
            'toilets' => "{$required}|integer|min:0",
            'status' => 'sometimes|in:active,inactive,maintenance',
            'is_featured' => 'sometimes|boolean',
            'check_in_time' => 'sometimes|date_format:H:i',
            'check_out_time' => 'sometimes|date_format:H:i',
            'house_rules' => 'nullable|string',
            'cancellation_policy' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string|max:255',
        ];
    }
}
