<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\Staff;
use App\Models\StaffSchedule;
use App\Services\RosterService;
use Illuminate\Http\Request;

class StaffScheduleController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $siteId = $request->integer('site_id') ?: null;
        $sites = Site::where('is_active', true)->orderBy('name')->get();

        // Only people who work at the chosen site can be planned there.
        $staff = Staff::with(['user', 'activeAssignments.site'])
            ->where('is_active', true)
            ->when($siteId, fn ($q) => $q->whereHas('assignments', fn ($a) => $a->active()->where('site_id', $siteId)))
            ->get()
            ->sortBy('user.name');

        $schedules = StaffSchedule::whereDate('date', $date)
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->with(['staff.user', 'site', 'attendance'])
            ->get();

        return view('admin.staff-schedules.index', compact('staff', 'schedules', 'date', 'sites', 'siteId'));
    }

    public function store(Request $request, RosterService $roster)
    {
        $data = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'site_id' => 'nullable|exists:sites,id',
            'date' => 'required|date',
            'shift_type' => 'required|in:' . implode(',', array_keys(StaffSchedule::SHIFT_TIMES)),
            'notes' => 'nullable|string|max:500',
        ]);

        $staff = Staff::findOrFail($data['staff_id']);
        // Default to the person's primary site when none is chosen.
        $site = isset($data['site_id']) ? Site::findOrFail($data['site_id']) : $staff->primaryAssignment?->site;
        if (!$site) {
            return back()->withErrors(['site_id' => ($staff->user->name ?? 'This staff member') . ' is not assigned to any site yet.'])->withInput();
        }

        $roster->schedule($staff, $site, $data['date'], $data['shift_type'], $data['notes'] ?? null, $request->user());

        return back()->with('success', 'Schedule created successfully');
    }

    public function updateStatus(Request $request, StaffSchedule $schedule)
    {
        $request->validate([
            'status' => 'required|in:scheduled,completed,cancelled,no_show',
        ]);

        $schedule->update(['status' => $request->status]);

        return back()->with('success', 'Status updated');
    }

    public function destroy(StaffSchedule $schedule)
    {
        $schedule->delete();
        return back()->with('success', 'Schedule deleted');
    }

    public function calendar(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $start = now()->setDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $schedules = StaffSchedule::whereBetween('date', [$start, $end])
            ->with('staff.user')
            ->get()
            ->groupBy('staff_id');

        return view('admin.staff-schedules.calendar', compact('schedules', 'month', 'year', 'start', 'end'));
    }
}
