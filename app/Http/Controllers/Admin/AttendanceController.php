<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AttendanceFilterRequest;
use App\Models\Attendance;
use App\Models\Site;
use App\Models\Staff;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Attendance across all sites: hours worked, lateness and location flags.
 * The CSV export feeds payroll.
 */
class AttendanceController extends Controller
{
    public function index(AttendanceFilterRequest $request)
    {
        [$query, $from, $to] = $this->filtered($request);

        $summary = (clone $query)->get()
            ->groupBy('staff_id')
            ->map(fn ($rows) => [
                'staff' => $rows->first()->staff,
                'shifts' => $rows->count(),
                'minutes' => $rows->sum(fn ($r) => $r->worked_minutes ?? 0),
                'late' => $rows->filter->isLate()->count(),
                'off_site' => $rows->where('clock_in_location', 'off_site')->count(),
                'auto_closed' => $rows->where('auto_closed', true)->count(),
            ])
            ->sortByDesc('minutes');

        $records = $query->with(['staff.user', 'site', 'schedule'])->latest('clock_in_at')->paginate(40)->withQueryString();

        return view('admin.attendance.index', [
            'records' => $records,
            'summary' => $summary,
            'sites' => Site::orderBy('name')->get(),
            'staffList' => Staff::with('user')->get()->sortBy('user.name'),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function export(AttendanceFilterRequest $request): StreamedResponse
    {
        [$query, $from, $to] = $this->filtered($request);
        $rows = $query->with(['staff.user', 'site', 'schedule'])->orderBy('clock_in_at')->get();

        $filename = 'attendance-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Employee ID', 'Name', 'Site', 'Shift', 'Clock in', 'Clock out', 'Hours', 'Late (min)', 'Clock-in location', 'Distance (m)', 'Flags', 'Handover note']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->staff->employee_id ?? '',
                    $r->staff->user->name ?? '',
                    $r->site->name ?? '',
                    $r->schedule?->label() ?? 'Unscheduled',
                    $r->clock_in_at->format('Y-m-d H:i'),
                    $r->clock_out_at?->format('Y-m-d H:i') ?? '',
                    $r->worked_minutes !== null ? number_format($r->worked_minutes / 60, 2) : '',
                    $r->late_minutes,
                    $r->clock_in_location,
                    $r->clock_in_distance_m ?? '',
                    implode('; ', $r->flags()),
                    $r->handover_note ?? '',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array{0: Builder, 1: CarbonImmutable, 2: CarbonImmutable}
     */
    private function filtered(AttendanceFilterRequest $request): array
    {
        $from = CarbonImmutable::parse($request->query('from', today()->startOfMonth()->toDateString()))->startOfDay();
        $to = CarbonImmutable::parse($request->query('to', today()->toDateString()))->endOfDay();

        $query = Attendance::query()
            ->whereBetween('clock_in_at', [$from, $to])
            ->when($request->filled('site_id'), fn ($q) => $q->where('site_id', $request->integer('site_id')))
            ->when($request->filled('staff_id'), fn ($q) => $q->where('staff_id', $request->integer('staff_id')));

        return [$query, $from, $to];
    }
}
