<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Staff;
use App\Models\StaffSchedule;
use Carbon\CarbonImmutable;

/**
 * Works out one staff member's payslip for a month from their salary structure,
 * attendance (overtime, absences) and recurring deductions.
 *
 *  - Overtime: time worked beyond the rostered shift, or any unrostered shift,
 *    paid at the person's hourly overtime rate (none if no rate is set).
 *  - Absence: a rostered shift in the month with no clock-in, deducted at
 *    monthly pay ÷ working days (if absence deductions apply to them).
 *  - Pension: employee and employer % of basic + housing + transport.
 *  - NHF: % of basic, if enrolled.
 *  - PAYE: monthly gross annualised, less pension, NHF and rent relief, taxed
 *    through the bands, divided by 12.
 */
class PayrollCalculator
{
    /**
     * @return array<string, mixed> payslip attributes, including 'details'
     */
    public function calculate(Staff $staff, CarbonImmutable $start, CarbonImmutable $end, array $settings): array
    {
        $salary = $staff->salaryStructure;
        $fixed = $salary->monthlyGross();

        // --- Attendance: overtime and site split
        $attendance = Attendance::where('staff_id', $staff->id)
            ->whereBetween('clock_in_at', [$start->startOfDay(), $end->endOfDay()])
            ->whereNotNull('worked_minutes')
            ->with('schedule')
            ->get();

        $overtimeMinutes = 0;
        foreach ($attendance as $record) {
            $planned = $record->schedule ? (int) $record->schedule->startsAt()->diffInMinutes($record->schedule->endsAt()) : 0;
            $overtimeMinutes += max(0, $record->worked_minutes - $planned);
        }
        $overtimeHours = round($overtimeMinutes / 60, 2);
        $overtimePay = $salary->overtime_rate ? round($overtimeHours * (float) $salary->overtime_rate, 2) : 0.0;

        // --- Absences: past rostered shifts nobody clocked in to
        $absentDays = StaffSchedule::where('staff_id', $staff->id)
            ->whereDate('date', '>=', $start)
            ->whereDate('date', '<=', min($end, CarbonImmutable::yesterday()))
            ->whereIn('status', ['scheduled', 'no_show'])
            ->whereDoesntHave('attendance')
            ->count();
        $dayRate = $fixed / max(1, (int) $settings['working_days_per_month']);
        $absenceDeduction = $salary->deduct_absences ? round(min($fixed, $absentDays * $dayRate), 2) : 0.0;

        $gross = round(max(0, $fixed + $overtimePay - $absenceDeduction), 2);

        // --- Statutory deductions
        $pensionBase = (float) $salary->basic + (float) $salary->housing + (float) $salary->transport;
        $pensionEmployee = $salary->pension_enabled ? round($pensionBase * $settings['pension_employee_rate'] / 100, 2) : 0.0;
        $pensionEmployer = $salary->pension_enabled ? round($pensionBase * $settings['pension_employer_rate'] / 100, 2) : 0.0;
        $nhf = $salary->nhf_enabled ? round((float) $salary->basic * $settings['nhf_rate'] / 100, 2) : 0.0;

        $rentRelief = $salary->annual_rent
            ? min((float) $salary->annual_rent * $settings['rent_relief_rate'] / 100, (float) $settings['rent_relief_cap'])
            : 0.0;
        $annualTaxable = max(0, $gross * 12 - ($pensionEmployee + $nhf) * 12 - $rentRelief);
        $paye = round($this->annualTax($annualTaxable, $settings['paye_bands']) / 12, 2);

        // --- Loans, advances and other deductions (never taking pay below zero)
        $available = max(0, $gross - $pensionEmployee - $nhf - $paye);
        $lines = [];
        foreach ($staff->deductions()->where('is_active', true)->orderBy('id')->get() as $deduction) {
            $amount = min($deduction->amountThisMonth(), $available);
            if ($amount <= 0) {
                continue;
            }
            $available -= $amount;
            $lines[] = ['id' => $deduction->id, 'type' => $deduction->type, 'description' => $deduction->description, 'amount' => round($amount, 2)];
        }
        $other = round(array_sum(array_column($lines, 'amount')), 2);

        $net = round($gross - $pensionEmployee - $nhf - $paye - $other, 2);

        return [
            'staff_id' => $staff->id,
            'basic' => $salary->basic,
            'housing' => $salary->housing,
            'transport' => $salary->transport,
            'other_allowance' => $salary->other_allowance,
            'overtime_hours' => $overtimeHours,
            'overtime_pay' => $overtimePay,
            'absent_days' => $absentDays,
            'absence_deduction' => $absenceDeduction,
            'gross' => $gross,
            'pension_employee' => $pensionEmployee,
            'pension_employer' => $pensionEmployer,
            'nhf' => $nhf,
            'paye' => $paye,
            'other_deductions' => $other,
            'net' => $net,
            'details' => [
                'deductions' => $lines,
                'hours_worked' => round($attendance->sum('worked_minutes') / 60, 2),
                'shifts_worked' => $attendance->count(),
                'rent_relief' => round($rentRelief, 2),
                'annual_taxable' => round($annualTaxable, 2),
                'site_split' => $this->siteSplit($staff, $attendance),
            ],
        ];
    }

    /**
     * Tax on an annual amount through the bands ([width|null, rate%] in order).
     */
    public function annualTax(float $taxable, array $bands): float
    {
        $tax = 0.0;
        $remaining = $taxable;
        foreach ($bands as [$width, $rate]) {
            if ($remaining <= 0) {
                break;
            }
            $slice = $width === null ? $remaining : min($remaining, (float) $width);
            $tax += $slice * ((float) $rate) / 100;
            $remaining -= $slice;
        }

        return round($tax, 2);
    }

    /**
     * Share of this person's cost per site, by hours worked there (primary site if none).
     *
     * @return array<int, float> site_id => fraction
     */
    private function siteSplit(Staff $staff, $attendance): array
    {
        $minutes = $attendance->groupBy('site_id')->map->sum('worked_minutes')->filter();
        $total = $minutes->sum();

        if ($total <= 0) {
            $primary = $staff->primaryAssignment?->site_id;

            return $primary ? [$primary => 1.0] : [];
        }

        return $minutes->map(fn ($m) => round($m / $total, 4))->all();
    }
}
