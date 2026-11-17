<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One month's payroll. Draft runs can be recalculated; approving freezes the
 * payslips and shows them to staff; marking paid reduces loan balances.
 */
class PayRun extends Model
{
    public const STATUSES = ['draft' => 'Draft', 'approved' => 'Approved', 'paid' => 'Paid'];

    protected $fillable = [
        'period_start', 'period_end', 'status', 'total_gross', 'total_deductions', 'total_net',
        'total_employer_pension', 'settings_snapshot', 'created_by', 'approved_by', 'approved_at', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'total_gross' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'total_net' => 'decimal:2',
            'total_employer_pension' => 'decimal:2',
            'settings_snapshot' => 'array',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
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
}
