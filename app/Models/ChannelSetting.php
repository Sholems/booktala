<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelSetting extends Model
{
    protected $fillable = [
        'channel_name',
        'is_enabled',
        'api_key',
        'api_secret',
        'property_id',
        'sync_frequency',
        'last_sync',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'last_sync' => 'datetime',
            'settings' => 'array',
        ];
    }

    public static function channels()
    {
        return [
            'bookingcom' => ['name' => 'Booking.com', 'icon' => 'fa-booking'],
            'expedia' => ['name' => 'Expedia', 'icon' => 'fa-expedia'],
            'airbnb' => ['name' => 'Airbnb', 'icon' => 'fa-airbnb'],
        ];
    }
}
