<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A staff member's monthly pay: basic plus allowances, and which statutory
 * deductions apply to them.
 */
class SalaryStructure extends Model
{
    protected $fillable = [
        'staff_id', 'basic', 'housing', 'transport', 'other_allowance', 'overtime_rate',
        'annual_rent', 'pension_enabled', 'nhf_enabled', 'deduct_absences',
    ];

    protected function casts(): array
    {
        return [
            'basic' => 'decimal:2',
            'housing' => 'decimal:2',
            'transport' => 'decimal:2',
            'other_allowance' => 'decimal:2',
            'overtime_rate' => 'decimal:2',
            'annual_rent' => 'decimal:2',
            'pension_enabled' => 'boolean',
            'nhf_enabled' => 'boolean',
            'deduct_absences' => 'boolean',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function monthlyGross(): float
    {
        return (float) $this->basic + (float) $this->housing + (float) $this->transport + (float) $this->other_allowance;
    }
}
