<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountCode extends Model
{
    protected $fillable = [
        'code',
        'description',
        'type',
        'value',
        'min_nights',
        'max_uses',
        'used_count',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_until' => 'date',
            'value' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function isValid(): bool
    {
        $now = now()->toDateString();

        return $this->is_active
            && $now >= $this->valid_from
            && $now <= $this->valid_until
            && ($this->max_uses === null || $this->used_count < $this->max_uses);
    }

    public function calculateDiscount($amount, $nights): float
    {
        if ($nights < $this->min_nights) {
            return 0;
        }

        return $this->type === 'percentage'
            ? ($amount * $this->value / 100)
            : $this->value;
    }
}
