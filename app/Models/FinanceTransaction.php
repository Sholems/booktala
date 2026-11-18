<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FinanceTransaction extends Model
{
    protected $fillable = [
        'reference',
        'idempotency_key',
        'payload_hash',
        'source_type',
        'source_id',
        'event_type',
        'currency',
        'occurred_at',
        'description',
        'reversal_of_id',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Posted journal records are immutable.'));
        static::deleting(fn () => throw new \LogicException('Posted journal records are immutable.'));
    }

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(FinanceEntry::class);
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(self::class, 'reversal_of_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
