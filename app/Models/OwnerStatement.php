<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A monthly statement for an owner: stays that ended in the month, less
 * commission and chargeable expenses, gives the payout. Draft statements can
 * be regenerated; approved ones are frozen and visible to the owner.
 */
class OwnerStatement extends Model
{
    public const STATUSES = ['draft' => 'Draft', 'approved' => 'Approved', 'paid' => 'Paid'];

    protected $fillable = [
        'owner_id', 'period_start', 'period_end', 'gross_revenue', 'commission_rate', 'commission_amount',
        'expenses_total', 'net_payout', 'nights_booked', 'status', 'approved_by', 'approved_at',
        'paid_at', 'payment_reference', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'gross_revenue' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'expenses_total' => 'decimal:2',
            'net_payout' => 'decimal:2',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OwnerStatementLine::class)->orderBy('date')->orderBy('id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function periodLabel(): string
    {
        return $this->period_start->format('F Y');
    }

    /** Owners see statements once head office has approved them. */
    public function scopeVisibleToOwner($query)
    {
        return $query->whereIn('status', ['approved', 'paid']);
    }
}
