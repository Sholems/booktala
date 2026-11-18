<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Incident;
use App\Models\InventoryItem;
use App\Models\MaintenanceReport;
use App\Models\PatrolCheckpoint;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\Staff;
use App\Models\StaffSchedule;
use App\Models\Task;
use App\Models\User;
use App\Models\VisitorLog;
use App\Rules\WorksAtProperty;
use App\Services\EscalationService;
use App\Services\RosterService;
use App\Services\WorkRouter;
use App\Support\Supervision;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Supervisor and manager tools for the sites they oversee: who is on duty,
 * what work is open or stuck, the roster, and attendance.
 */
class TeamController extends Controller
{
    public function index(Request $request)
    {
        [$sites, $siteIds] = $this->sites($request);

        $shifts = StaffSchedule::whereIn('site_id', $siteIds)
            ->whereDate('date', today())
            ->where('status', '!=', 'cancelled')
            ->with(['staff.user', 'site', 'attendance'])
            ->get()
            ->sortBy(fn (StaffSchedule $s) => $s->startsAt());

        $onDuty = Attendance::whereIn('site_id', $siteIds)
            ->whereNull('clock_out_at')
            ->with(['staff.user', 'staff.primaryAssignment.position', 'site'])
            ->orderBy('clock_in_at')
            ->get();

        $propertyIds = fn () => Property::whereIn('site_id', $siteIds)->select('id');

        $tasks = Task::whereIn('property_id', $propertyIds())
            ->where('status', '!=', 'completed')
            ->with(['property.site', 'assignedTo'])
            ->orderByDesc('escalation_level')
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->orderBy('due_date')
            ->get();

        $repairs = MaintenanceReport::whereIn('property_id', $propertyIds())
            ->where('status', '!=', 'resolved')
            ->with(['property.site', 'assignedTo'])
            ->orderByDesc('escalation_level')
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'emergency' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->get();

        $handovers = Attendance::whereIn('site_id', $siteIds)
            ->whereNotNull('handover_note')
            ->where('clock_out_at', '>=', now()->subDay())
            ->with(['staff.user', 'site'])
            ->latest('clock_out_at')
            ->limit(6)
            ->get();

        $arrivals = Reservation::whereIn('property_id', $propertyIds())->inQueue('arrivals')->count();
        $departures = Reservation::whereIn('property_id', $propertyIds())->inQueue('departures')->count();

        return view('staff.team.index', [
            'sites' => $sites,
            'selectedSite' => $request->integer('site') ?: null,
            'shifts' => $shifts,
            'onDuty' => $onDuty,
            'tasks' => $tasks,
            'repairs' => $repairs,
            'handovers' => $handovers,
            'arrivals' => $arrivals,
            'departures' => $departures,
            'teamBySite' => $this->teamBySite($siteIds),
            'missedGrace' => EscalationService::MISSED_SHIFT_GRACE_MINUTES,
            'security' => [
                'open_incidents' => Incident::whereIn('site_id', $siteIds)->where('status', 'open')->count(),
                'visitors_on_site' => VisitorLog::whereIn('site_id', $siteIds)->onSite()->count(),
                'low_stock' => InventoryItem::whereIn('site_id', $siteIds)->where('is_active', true)->low()->count(),
                'overdue_checkpoints' => PatrolCheckpoint::whereIn('site_id', $siteIds)->where('is_active', true)
                    ->with(['site', 'lastScan'])->get()
                    ->filter(fn ($c) => $c->site->patrolsAt(now())
                        && (! $c->lastScan || $c->lastScan->scanned_at->lt(now()->subMinutes($c->site->patrol_interval_minutes * 2))))
                    ->count(),
            ],
        ]);
    }

    public function assignTask(Request $request, Task $task)
    {
        $this->ensureSupervises($request->user(), $task->property?->site_id);
        $data = $request->validate(['assigned_to' => ['required', new WorksAtProperty($task->property_id)]]);

        $task->update(['assigned_to' => $data['assigned_to'], 'assigned_by' => $request->user()->id]);
        app(WorkRouter::class)->notifyAssigned($task->assignedTo, $task->title, 'Assigned by '.$request->user()->name.' · '.($task->property->title ?? ''), route('staff.tasks.show', $task));

        return back()->with('success', 'Task assigned to '.$task->assignedTo->name);
    }

    public function assignRepair(Request $request, MaintenanceReport $maintenanceReport)
    {
        $this->ensureSupervises($request->user(), $maintenanceReport->property?->site_id);
        $data = $request->validate(['assigned_to' => ['required', new WorksAtProperty($maintenanceReport->property_id)]]);

        $maintenanceReport->update(['assigned_to' => $data['assigned_to']]);
        app(WorkRouter::class)->notifyAssigned($maintenanceReport->assignedTo, 'Maintenance job: '.$maintenanceReport->title, 'Assigned by '.$request->user()->name.' · '.($maintenanceReport->property->title ?? ''), route('staff.maintenance.index'));

        return back()->with('success', 'Repair assigned to '.$maintenanceReport->assignedTo->name);
    }

    public function roster(Request $request)
    {
        [$sites] = $this->sites($request, all: true);
        $site = $sites->firstWhere('id', $request->integer('site')) ?? $sites->first();

        $weekStart = CarbonImmutable::parse($request->query('week', today()->toDateString()))->startOfWeek();
        $days = collect(range(0, 6))->map(fn ($i) => $weekStart->addDays($i));

        $team = $this->teamBySite(collect([$site->id]))->get($site->id, collect());
        $shifts = StaffSchedule::where('site_id', $site->id)
            ->whereDate('date', '>=', $weekStart)
            ->whereDate('date', '<=', $weekStart->addDays(6))
            ->with(['attendance'])
            ->get()
            ->groupBy(fn ($s) => $s->staff_id.'|'.$s->date->toDateString());

        return view('staff.team.roster', compact('sites', 'site', 'weekStart', 'days', 'team', 'shifts'));
    }

    public function storeShift(Request $request, RosterService $roster)
    {
        $data = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'staff_id' => 'required|exists:staff,id',
            'date' => 'required|date|after_or_equal:today',
            'shift_type' => 'required|in:'.implode(',', array_keys(StaffSchedule::SHIFT_TIMES)),
            'notes' => 'nullable|string|max:500',
        ]);
        $this->ensureSupervises($request->user(), (int) $data['site_id']);

        $shift = $roster->schedule(Staff::findOrFail($data['staff_id']), Site::findOrFail($data['site_id']), $data['date'], $data['shift_type'], $data['notes'] ?? null, $request->user());

        return back()->with('success', ($shift->staff->user->name ?? 'Staff').' scheduled: '.$shift->date->format('D j M').', '.$shift->label());
    }

    public function cancelShift(Request $request, StaffSchedule $schedule)
    {
        $this->ensureSupervises($request->user(), $schedule->site_id);

        if ($schedule->attendance()->exists()) {
            return back()->with('error', 'This shift has already been clocked in to, so it cannot be cancelled.');
        }

        $schedule->update(['status' => 'cancelled']);

        return back()->with('success', 'Shift cancelled');
    }

    public function attendance(Request $request)
    {
        [$sites, $siteIds] = $this->sites($request);
        $from = CarbonImmutable::parse($request->query('from', today()->subDays(13)->toDateString()))->startOfDay();
        $to = CarbonImmutable::parse($request->query('to', today()->toDateString()))->endOfDay();

        $records = Attendance::whereIn('site_id', $siteIds)
            ->whereBetween('clock_in_at', [$from, $to])
            ->with(['staff.user', 'site', 'schedule'])
            ->latest('clock_in_at')
            ->paginate(40)
            ->withQueryString();

        return view('staff.team.attendance', [
            'sites' => $sites,
            'selectedSite' => $request->integer('site') ?: null,
            'records' => $records,
            'from' => $from,
            'to' => $to,
        ]);
    }

    /**
     * The sites this supervisor oversees, optionally narrowed to ?site=.
     *
     * @return array{0: Collection<int, Site>, 1: Collection<int, int>}
     */
    private function sites(Request $request, bool $all = false): array
    {
        $ids = Supervision::siteIds($request->user());
        $sites = Site::whereIn('id', $ids)->orderBy('name')->get();

        $selected = $request->integer('site');
        if (! $all && $selected && $ids->contains($selected)) {
            return [$sites, collect([$selected])];
        }
        if ($selected && ! $ids->contains($selected)) {
            abort(404);
        }

        return [$sites, $ids];
    }

    /**
     * Staff working at each site today, for assignment and roster lists.
     *
     * @return Collection<int, Collection<int, User>>
     */
    private function teamBySite(Collection $siteIds): Collection
    {
        $users = User::role('staff')
            ->where('status', 'active')
            ->whereHas('staff.assignments', fn ($q) => $q->active()->whereIn('site_id', $siteIds))
            ->with(['staff.activeAssignments.position'])
            ->orderBy('name')
            ->get();

        return $siteIds->mapWithKeys(fn ($siteId) => [
            $siteId => $users->filter(fn (User $u) => $u->staff->activeAssignments->contains('site_id', $siteId))->values(),
        ]);
    }

    private function ensureSupervises(User $user, ?int $siteId): void
    {
        abort_unless(Supervision::supervises($user, $siteId), 404);
    }
}
