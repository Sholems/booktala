<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * A staff member's own shifts, as planned by admin on the Staff Schedules page.
 */
class ScheduleController extends Controller
{
    public function index()
    {
        $upcoming = Auth::user()->staffSchedules()
            ->whereDate('date', '>=', today())
            ->orderBy('date')
            ->limit(30)
            ->get();

        $recent = Auth::user()->staffSchedules()
            ->whereDate('date', '<', today())
            ->whereDate('date', '>=', today()->subDays(14))
            ->orderByDesc('date')
            ->get();

        return view('staff.schedule.index', compact('upcoming', 'recent'));
    }
}
