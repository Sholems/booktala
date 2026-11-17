<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A recurring payroll deduction such as a loan or salary advance. When it has a
 * balance, each paid pay run reduces it and it stops once cleared.
 */
class StaffDeduction extends Model
{
    public const TYPES = ['loan' => 'Loan', 'advance' => 'Salary advance', 'other' => 'Other'];

    protected $fillable = ['staff_id', 'type', 'description', 'monthly_amount', 'balance', 'is_active'];

    protected function casts(): array
    {
        return ['monthly_amount' => 'decimal:2', 'balance' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /** What this month takes: the instalment, or what is left if less. */
    public function amountThisMonth(): float
    {
        $amount = (float) $this->monthly_amount;

        return $this->balance === null ? $amount : min($amount, max(0, (float) $this->balance));
    }
}
