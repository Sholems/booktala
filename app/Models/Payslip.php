<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    protected $fillable = [
        'pay_run_id', 'staff_id', 'basic', 'housing', 'transport', 'other_allowance', 'overtime_hours',
        'overtime_pay', 'absent_days', 'absence_deduction', 'gross', 'pension_employee', 'pension_employer',
        'nhf', 'paye', 'other_deductions', 'net', 'details',
    ];

    protected function casts(): array
    {
        $money = array_fill_keys([
            'basic', 'housing', 'transport', 'other_allowance', 'overtime_hours', 'overtime_pay', 'absence_deduction',
            'gross', 'pension_employee', 'pension_employer', 'nhf', 'paye', 'other_deductions', 'net',
        ], 'decimal:2');

        return $money + ['details' => 'array', 'absent_days' => 'integer'];
    }

    public function payRun(): BelongsTo
    {
        return $this->belongsTo(PayRun::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function totalDeductions(): float
    {
        return (float) $this->pension_employee + (float) $this->nhf + (float) $this->paye + (float) $this->other_deductions;
    }
}
