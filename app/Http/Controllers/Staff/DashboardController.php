<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\MaintenanceReport;
use App\Models\Reservation;
use App\Models\Task;
use App\Services\OperationsOverview;
use App\Support\Supervision;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $staff = Auth::user();

        $tasks = Task::where('assigned_to', $staff->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->with('property')
            ->orderBy('due_date')
            ->take(10)
            ->get();

        // Only this staff member's sites.
        $todayCheckIns = Reservation::visibleTo($staff)
            ->whereDate('check_in', today())
            ->where('booking_status', 'confirmed')
            ->with('property', 'user', 'guestCheckin')
            ->get();

        $recentMaintenances = MaintenanceReport::visibleTo($staff)
            ->where('status', '!=', 'resolved')
            ->with('property')
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'emergency' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $today = app(OperationsOverview::class)->staffToday($staff);
        $todayShift = $staff->staffSchedules()->whereDate('date', today())->with('site')->first();

        // Clock in/out card: where they can clock in, whether they are on, and notes left by the last shift.
        $profile = $staff->staff;
        $clockSites = $profile ? $profile->activeAssignments()->with('site')->get()->pluck('site')->unique('id')->values() : collect();
        $openAttendance = $profile?->openAttendance()->with('site')->first();
        $handovers = $clockSites->isEmpty() ? collect() : Attendance::whereIn('site_id', $clockSites->pluck('id'))
            ->whereNotNull('handover_note')
            ->where('clock_out_at', '>=', now()->subDay())
            ->where('staff_id', '!=', $profile->id)
            ->with(['staff.user', 'site'])
            ->latest('clock_out_at')
            ->limit(3)
            ->get();
        $isSupervisor = Supervision::isSupervisor($staff);

        return view('staff.dashboard', compact('tasks', 'todayCheckIns', 'recentMaintenances', 'today', 'todayShift', 'clockSites', 'openAttendance', 'handovers', 'isSupervisor'));
    }
}
