<?php

namespace App\Http\Requests\Admin;

use App\Models\OtaChannelMapping;
use App\Support\PropertyAccess;
use Illuminate\Foundation\Http\FormRequest;

class StoreChannelMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $propertyId = $this->input('property_id');

        return $this->user() && PropertyAccess::canAccess($this->user(), (int) $propertyId);
    }

    public function rules(): array
    {
        $allowedChannels = implode(',', array_keys(OtaChannelMapping::CHANNELS));

        return [
            'property_id' => ['required', 'exists:properties,id'],
            'channel' => ['required', 'string', "in:{$allowedChannels}"],
            'import_ical_url' => ['nullable', 'url', 'max:2000'],
            'rate_multiplier' => ['nullable', 'numeric', 'min:0.5', 'max:3.0'],
            'min_stay_override' => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }

    public function messages(): array
    {
        return [
            'property_id.required' => 'Please select a unit to link with this channel.',
            'property_id.exists' => 'The selected unit does not exist.',
            'channel.required' => 'Please choose an OTA channel (e.g. Airbnb, Booking.com, VRBO).',
            'import_ical_url.url' => 'The inbound iCal link must be a valid URL starting with http:// or https://.',
            'rate_multiplier.min' => 'Rate multiplier cannot be lower than 0.5 (50% discount).',
            'rate_multiplier.max' => 'Rate multiplier cannot exceed 3.0 (300% markup).',
        ];
    }
}
