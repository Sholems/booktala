<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    /** type => [label, staff can record it] */
    public const TYPES = [
        'used' => ['Used', true],
        'to_laundry' => ['Sent to laundry', true],
        'from_laundry' => ['Back from laundry', true],
        'discarded' => ['Damaged / lost', true],
        'received' => ['Received', false],
        'count' => ['Stock count', false],
    ];

    protected $fillable = ['inventory_item_id', 'type', 'quantity', 'on_hand_after', 'property_id', 'unit_cost', 'note', 'user_id'];

    protected function casts(): array
    {
        return ['quantity' => 'float', 'on_hand_after' => 'float', 'unit_cost' => 'float'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return self::TYPES[$this->type][0] ?? ucfirst($this->type);
    }
}
