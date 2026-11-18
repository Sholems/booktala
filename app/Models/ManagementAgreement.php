<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManagementAgreement extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_REVIEWED = 'reviewed';

    public const STATUS_RETIRED = 'retired';

    protected $fillable = [
        'unit_id',
        'owner_id',
        'commission_rate',
        'deduct_expenses',
        'caution_forfeiture_recipient',
        'effective_from',
        'effective_to',
        'status',
        'created_by',
        'reviewed_by',
        'reviewed_at',
        'notes',
    ];

    protected static function booted(): void
    {
        static::updating(function (ManagementAgreement $agreement) {
            if ($agreement->getOriginal('status') === self::STATUS_DRAFT) {
                return;
            }

            $changedTerms = array_diff(array_keys($agreement->getDirty()), ['status', 'updated_at']);
            $isRetirement = $agreement->status === self::STATUS_RETIRED && $changedTerms === [];

            if (! $isRetirement) {
                throw new \LogicException('Reviewed management agreement terms are immutable.');
            }
        });

        static::deleting(function (ManagementAgreement $agreement) {
            if ($agreement->status !== self::STATUS_DRAFT) {
                throw new \LogicException('Reviewed management agreements cannot be deleted.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'deduct_expenses' => 'boolean',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function financeEntries(): HasMany
    {
        return $this->hasMany(FinanceEntry::class);
    }
}
