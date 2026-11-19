<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OtaChannelMapping extends Model
{
    public const CHANNEL_AIRBNB = 'airbnb';

    public const CHANNEL_BOOKINGCOM = 'bookingcom';

    public const CHANNEL_VRBO = 'vrbo';

    public const CHANNEL_EXPEDIA = 'expedia';

    public const CHANNEL_OTHER = 'other';

    public const CHANNELS = [
        self::CHANNEL_AIRBNB => [
            'name' => 'Airbnb',
            'icon' => 'fab fa-airbnb',
            'color' => '#FF5A5F',
            'default_markup' => 1.15, // 15% channel commission buffer
        ],
        self::CHANNEL_BOOKINGCOM => [
            'name' => 'Booking.com',
            'icon' => 'fas fa-bed',
            'color' => '#003580',
            'default_markup' => 1.15,
        ],
        self::CHANNEL_VRBO => [
            'name' => 'VRBO',
            'icon' => 'fas fa-home',
            'color' => '#1c3c6d',
            'default_markup' => 1.10,
        ],
        self::CHANNEL_EXPEDIA => [
            'name' => 'Expedia',
            'icon' => 'fas fa-plane',
            'color' => '#FFCC00',
            'default_markup' => 1.15,
        ],
        self::CHANNEL_OTHER => [
            'name' => 'Other OTA',
            'icon' => 'fas fa-calendar-alt',
            'color' => '#64748b',
            'default_markup' => 1.00,
        ],
    ];

    protected $fillable = [
        'property_id',
        'channel',
        'export_token',
        'import_ical_url',
        'rate_multiplier',
        'min_stay_override',
        'sync_status',
        'last_synced_at',
        'last_error',
        'conflict_details',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate_multiplier' => 'decimal:2',
            'min_stay_override' => 'integer',
            'last_synced_at' => 'datetime',
            'conflict_details' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $mapping) {
            if (empty($mapping->export_token)) {
                $mapping->export_token = Str::random(40);
            }
        });
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'property_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function getChannelMetaAttribute(): array
    {
        return self::CHANNELS[$this->channel] ?? self::CHANNELS[self::CHANNEL_OTHER];
    }

    public function getExportUrlAttribute(): string
    {
        return route('ota.feed', ['token' => $this->export_token]);
    }
}
