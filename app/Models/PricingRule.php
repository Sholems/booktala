<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingRule extends Model
{
    public const TYPE_SEASONAL = 'seasonal';

    public const TYPE_WEEKEND = 'weekend';

    public const TYPE_EVENT = 'event';

    public const TYPE_LENGTH_OF_STAY = 'length_of_stay';

    public const TYPE_PROMO = 'promo';

    public const ADJUSTMENT_FIXED_OVERRIDE = 'fixed_override';

    public const ADJUSTMENT_PERCENTAGE_MARKUP = 'percentage_markup';

    public const ADJUSTMENT_PERCENTAGE_DISCOUNT = 'percentage_discount';

    public const ADJUSTMENT_FIXED_MARKUP = 'fixed_markup';

    protected $fillable = [
        'property_id',
        'site_id',
        'name',
        'start_date',
        'end_date',
        'price_override',
        'discount_percentage',
        'adjustment_type',
        'adjustment_value',
        'min_nights',
        'days_of_week',
        'priority',
        'type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'price_override' => 'decimal:2',
            'discount_percentage' => 'decimal:2',
            'adjustment_value' => 'decimal:2',
            'min_nights' => 'integer',
            'priority' => 'integer',
            'days_of_week' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'property_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
