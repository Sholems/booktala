<?php

namespace App\Http\Requests\Admin;

use App\Support\PropertyAccess;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePricingRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pricingRule = $this->route('pricingRule');
        if ($pricingRule && $pricingRule->property_id) {
            if (! PropertyAccess::canAccess($this->user(), (int) $pricingRule->property_id)) {
                return false;
            }
        }

        $newPropertyId = $this->input('property_id');
        if ($newPropertyId) {
            return $this->user() && PropertyAccess::canAccess($this->user(), (int) $newPropertyId);
        }

        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:seasonal,weekend,event,length_of_stay,promo'],
            'property_id' => ['nullable', 'exists:properties,id'],
            'site_id' => ['nullable', 'exists:sites,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'adjustment_type' => ['required', 'in:fixed_override,percentage_markup,percentage_discount,fixed_markup'],
            'adjustment_value' => ['nullable', 'numeric', 'min:0'],
            'price_override' => ['nullable', 'numeric', 'min:0'],
            'min_nights' => ['nullable', 'integer', 'min:1', 'max:365'],
            'days_of_week' => ['nullable', 'array'],
            'days_of_week.*' => ['integer', 'min:1', 'max:7'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
