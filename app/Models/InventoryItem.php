<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Stock kept at a site: linen, toiletries, cleaning supplies, spares. Linen can
 * also be tracked through the laundry (sent out, back in).
 */
class InventoryItem extends Model
{
    use \App\Traits\VisibleBySite;

    public const CATEGORIES = [
        'linen' => 'Linen & towels',
        'toiletries' => 'Guest toiletries',
        'cleaning' => 'Cleaning supplies',
        'kitchen' => 'Kitchen & consumables',
        'maintenance' => 'Maintenance spares',
        'other' => 'Other',
    ];

    protected $fillable = [
        'site_id', 'name', 'category', 'unit', 'on_hand', 'at_laundry', 'reorder_level',
        'tracks_laundry', 'is_active', 'low_stock_alerted_at',
    ];

    protected function casts(): array
    {
        return [
            'on_hand' => 'float',
            'at_laundry' => 'float',
            'reorder_level' => 'float',
            'tracks_laundry' => 'boolean',
            'is_active' => 'boolean',
            'low_stock_alerted_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest();
    }

    public function isLow(): bool
    {
        return $this->reorder_level > 0 && $this->on_hand <= $this->reorder_level;
    }

    public function scopeLow($query)
    {
        return $query->where('reorder_level', '>', 0)->whereColumn('on_hand', '<=', 'reorder_level');
    }

    public function quantityLabel(float $quantity): string
    {
        return rtrim(rtrim(number_format($quantity, 2), '0'), '.') . ' ' . $this->unit;
    }
}
