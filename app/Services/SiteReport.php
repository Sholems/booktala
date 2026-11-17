<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Expense;
use App\Models\Incident;
use App\Models\InventoryItem;
use App\Models\MaintenanceReport;
use App\Models\PatrolScan;
use App\Models\Payslip;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\StaffSchedule;
use App\Models\Task;
use App\Models\VisitorLog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * One row per site comparing how each location is doing over a period:
 * occupancy and revenue, costs, staff attendance, work and security.
 */
class SiteReport
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $from = $from->startOfDay();
        $to = $to->endOfDay();
        $days = (int) $from->diffInDays($to->addSecond()->startOfDay());
        $staffCost = $this->staffCostBySite($from, $to);

        return Site::where('is_active', true)->with('properties')->orderBy('name')->get()->map(function (Site $site) use ($from, $to, $days, $staffCost) {
            $propertyIds = $site->properties->pluck('id');
            $active = $site->properties->where('status', 'active')->count();

            // Nights booked inside the period (stays crossing the edges count only their part).
            $booked = Reservation::whereIn('property_id', $propertyIds)
                ->whereIn('booking_status', ['confirmed', 'checked_in', 'checked_out'])
                ->whereDate('check_in', '<=', $to)
                ->whereDate('check_out', '>', $from)
                ->get(['check_in', 'check_out'])
                ->sum(fn ($r) => max(0, (int) CarbonImmutable::parse($r->check_in)->max($from->startOfDay())
                    ->diffInDays(CarbonImmutable::parse($r->check_out)->min($to->addSecond()->startOfDay()))));
            $available = $active * $days;

            $revenue = (float) Reservation::whereIn('property_id', $propertyIds)
                ->where('booking_status', 'checked_out')->where('payment_status', 'paid')
                ->whereDate('check_out', '>=', $from)->whereDate('check_out', '<=', $to)
                ->sum(\DB::raw('subtotal - discount_amount'));

            $expenses = Expense::whereIn('property_id', $propertyIds)->whereDate('spent_on', '>=', $from)->whereDate('spent_on', '<=', $to)
                ->selectRaw('charge_to, SUM(amount) as total')->groupBy('charge_to')->pluck('total', 'charge_to');

            $attendance = Attendance::where('site_id', $site->id)->whereBetween('clock_in_at', [$from, $to])->get();
            $missedShifts = StaffSchedule::where('site_id', $site->id)
                ->whereDate('date', '>=', $from)->whereDate('date', '<=', min($to, CarbonImmutable::yesterday()->endOfDay()))
                ->whereIn('status', ['scheduled', 'no_show'])
                ->whereDoesntHave('attendance')
                ->count();

            $completed = Task::whereIn('property_id', $propertyIds)->whereBetween('completed_at', [$from, $to])->get(['created_at', 'completed_at']);
            $resolved = MaintenanceReport::whereIn('property_id', $propertyIds)->whereBetween('resolved_at', [$from, $to])->get(['created_at', 'resolved_at']);
            $incidents = Incident::where('site_id', $site->id)->whereBetween('occurred_at', [$from, $to])->get(['severity']);

            return [
                'site' => $site,
                'properties' => $site->properties->count(),
                'occupancy' => $available ? round($booked / $available * 100, 1) : null,
                'nights_booked' => $booked,
                'revenue' => round($revenue, 2),
                'owner_expenses' => round((float) ($expenses['owner'] ?? 0), 2),
                'company_expenses' => round((float) ($expenses['company'] ?? 0), 2),
                'staff_cost' => round($staffCost[$site->id] ?? 0, 2),
                'hours_worked' => round($attendance->sum('worked_minutes') / 60, 1),
                'late_clock_ins' => $attendance->filter->isLate()->count(),
                'off_site_clock_ins' => $attendance->where('clock_in_location', 'off_site')->count(),
                'missed_shifts' => $missedShifts,
                'tasks_completed' => $completed->count(),
                'avg_task_hours' => $completed->isEmpty() ? null : round($completed->avg(fn ($t) => $t->created_at->diffInMinutes($t->completed_at)) / 60, 1),
                'tasks_escalated' => Task::whereIn('property_id', $propertyIds)->whereBetween('escalated_at', [$from, $to])->count()
                    + MaintenanceReport::whereIn('property_id', $propertyIds)->whereBetween('escalated_at', [$from, $to])->count(),
                'repairs_reported' => MaintenanceReport::whereIn('property_id', $propertyIds)->whereBetween('created_at', [$from, $to])->count(),
                'repairs_resolved' => $resolved->count(),
                'avg_repair_hours' => $resolved->isEmpty() ? null : round($resolved->avg(fn ($r) => $r->created_at->diffInMinutes($r->resolved_at)) / 60, 1),
                'incidents' => $incidents->count(),
                'serious_incidents' => $incidents->whereIn('severity', ['high', 'critical'])->count(),
                'open_incidents' => Incident::where('site_id', $site->id)->where('status', 'open')->count(),
                'visitors' => VisitorLog::where('site_id', $site->id)->whereBetween('arrived_at', [$from, $to])->count(),
                'patrol_scans' => PatrolScan::whereHas('checkpoint', fn ($q) => $q->where('site_id', $site->id))->whereBetween('scanned_at', [$from, $to])->count(),
                'low_stock_items' => InventoryItem::where('site_id', $site->id)->where('is_active', true)->low()->count(),
            ];
        });
    }

    /**
     * Employer cost (gross + employer pension) from approved pay runs in the
     * period, split across sites by hours worked.
     *
     * @return array<int, float>
     */
    private function staffCostBySite(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $costs = [];
        $slips = Payslip::whereHas('payRun', fn ($q) => $q->whereIn('status', ['approved', 'paid'])
            ->whereDate('period_start', '>=', $from->startOfMonth())->whereDate('period_start', '<=', $to))->get();

        foreach ($slips as $slip) {
            $cost = (float) $slip->gross + (float) $slip->pension_employer;
            foreach ($slip->details['site_split'] ?? [] as $siteId => $share) {
                $costs[(int) $siteId] = ($costs[(int) $siteId] ?? 0) + $cost * $share;
            }
        }

        return $costs;
    }

    /** Column headings for the CSV, in order. */
    public const COLUMNS = [
        'properties' => 'Properties',
        'occupancy' => 'Occupancy %',
        'nights_booked' => 'Nights booked',
        'revenue' => 'Room revenue',
        'owner_expenses' => 'Owner expenses',
        'company_expenses' => 'Company expenses',
        'staff_cost' => 'Staff cost',
        'hours_worked' => 'Hours worked',
        'late_clock_ins' => 'Late clock-ins',
        'off_site_clock_ins' => 'Off-site clock-ins',
        'missed_shifts' => 'Missed shifts',
        'tasks_completed' => 'Tasks completed',
        'avg_task_hours' => 'Avg hours to complete task',
        'tasks_escalated' => 'Work escalated',
        'repairs_reported' => 'Repairs reported',
        'repairs_resolved' => 'Repairs resolved',
        'avg_repair_hours' => 'Avg hours to resolve repair',
        'incidents' => 'Incidents',
        'serious_incidents' => 'High/critical incidents',
        'open_incidents' => 'Open incidents (now)',
        'visitors' => 'Visitors',
        'patrol_scans' => 'Patrol scans',
        'low_stock_items' => 'Low-stock items (now)',
    ];
}
