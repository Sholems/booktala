<?php

namespace App\Services;

use App\Models\PayRun;
use App\Models\Staff;
use App\Models\StaffDeduction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Monthly pay runs: generate (or recalculate a draft), approve, mark paid.
 */
class PayRunService
{
    public function __construct(private PayrollCalculator $calculator, private PayrollSettings $settings) {}

    public function generate(CarbonImmutable $month, User $by): PayRun
    {
        $start = $month->startOfMonth();
        $end = $month->endOfMonth();

        // Match on the date only: stored dates carry a time part on some databases.
        $run = PayRun::whereDate('period_start', $start)->first() ?? new PayRun(['period_start' => $start->toDateString()]);
        if ($run->exists && ! $run->isDraft()) {
            throw ValidationException::withMessages(['month' => 'The '.$run->periodLabel().' pay run is already '.$run->status.' and cannot be recalculated.']);
        }

        return DB::transaction(function () use ($run, $start, $end, $by) {
            $settings = $this->settings->all();
            $run->fill([
                'period_end' => $end->toDateString(),
                'status' => 'draft',
                'settings_snapshot' => $settings,
                'created_by' => $run->created_by ?? $by->id,
            ])->save();
            $run->payslips()->delete();

            $staff = Staff::where('is_active', true)
                ->whereHas('salaryStructure')
                ->whereHas('user', fn ($q) => $q->where('status', 'active'))
                ->with(['salaryStructure', 'user', 'primaryAssignment'])
                ->get();

            foreach ($staff as $member) {
                $run->payslips()->create($this->calculator->calculate($member, $start, $end, $settings));
            }

            return $this->refreshTotals($run);
        });
    }

    public function approve(PayRun $run, User $by): void
    {
        if (! $run->isDraft()) {
            throw ValidationException::withMessages(['status' => 'Only a draft pay run can be approved.']);
        }
        if (! $run->payslips()->exists()) {
            throw ValidationException::withMessages(['status' => 'This pay run has no payslips. Set salaries first.']);
        }

        $run->update(['status' => 'approved', 'approved_by' => $by->id, 'approved_at' => now()]);
    }

    /**
     * Record that salaries went out, and take this month's instalments off loan balances.
     */
    public function markPaid(PayRun $run): void
    {
        if ($run->status !== 'approved') {
            throw ValidationException::withMessages(['status' => 'Approve the pay run before marking it paid.']);
        }

        DB::transaction(function () use ($run) {
            foreach ($run->payslips as $payslip) {
                foreach ($payslip->details['deductions'] ?? [] as $line) {
                    $deduction = StaffDeduction::find($line['id']);
                    if ($deduction && $deduction->balance !== null) {
                        $balance = max(0, round((float) $deduction->balance - (float) $line['amount'], 2));
                        $deduction->update(['balance' => $balance, 'is_active' => $balance > 0]);
                    }
                }
            }
            $run->update(['status' => 'paid', 'paid_at' => now()]);
        });
    }

    private function refreshTotals(PayRun $run): PayRun
    {
        $slips = $run->payslips()->get();
        $run->update([
            'total_gross' => $slips->sum('gross'),
            'total_deductions' => $slips->sum(fn ($s) => $s->totalDeductions()),
            'total_net' => $slips->sum('net'),
            'total_employer_pension' => $slips->sum('pension_employer'),
        ]);

        return $run->fresh();
    }
}
