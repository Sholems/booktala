<?php

namespace App\Services;

use App\Models\MaintenanceReport;
use App\Models\Property;
use App\Models\StaffSchedule;
use App\Models\User;
use App\Notifications\OperationsAlert;
use App\Support\PropertyAccess;

/**
 * Picks who should do a piece of work at a property: someone assigned to the
 * property's site, in the right department, on shift today (clocked in first),
 * with the fewest open jobs. Nobody suitable means the work stays unassigned
 * and the escalation check brings it to a supervisor.
 */
class WorkRouter
{
    /**
     * @param  array<int, string>  $departments  preferred first; others at the site are a fallback only if $strict is false
     */
    public function pick(Property|int $property, array $departments, bool $onShiftOnly = true, bool $strict = true): ?User
    {
        $property = $property instanceof Property ? $property : Property::find($property);
        if (! $property) {
            return null;
        }

        $candidates = PropertyAccess::staffAtProperty($property)
            ->with(['staff.activeAssignments.position', 'staff.openAttendance'])
            ->withCount(['assignedTasks as open_tasks_count' => fn ($q) => $q->whereIn('status', ['pending', 'in_progress'])])
            ->get();

        if ($onShiftOnly) {
            $onShift = StaffSchedule::whereDate('date', today())
                ->where('status', 'scheduled')
                ->where(fn ($q) => $q->whereNull('site_id')->orWhere('site_id', $property->site_id))
                ->pluck('staff_id');
            $candidates = $candidates->filter(fn (User $u) => $onShift->contains($u->staff->id)
                || $u->staff->openAttendance?->site_id === $property->site_id);
        }

        $inDepartment = fn (User $u) => $u->staff->activeAssignments
            ->where('site_id', $property->site_id)
            ->contains(fn ($a) => in_array($a->position->department, $departments, true));

        $pool = $candidates->filter($inDepartment);
        if ($pool->isEmpty() && ! $strict) {
            $pool = $candidates;
        }

        return $pool->sortBy([
            // Clocked in here right now beats merely scheduled.
            fn (User $a, User $b) => (int) ($b->staff->openAttendance?->site_id === $property->site_id) <=> (int) ($a->staff->openAttendance?->site_id === $property->site_id),
            fn (User $a, User $b) => $a->open_tasks_count <=> $b->open_tasks_count,
            fn (User $a, User $b) => $a->id <=> $b->id,
        ])->first();
    }

    /**
     * Give a new maintenance report to a technician at the site, and tell them.
     */
    public function routeMaintenance(MaintenanceReport $report): ?User
    {
        $technician = $this->pick($report->property_id, ['maintenance']);
        if (! $technician) {
            return null;
        }

        $report->update(['assigned_to' => $technician->id]);
        $this->notifyAssigned($technician, 'Maintenance job: '.$report->title,
            ($report->property->title ?? 'A property').' · '.ucfirst($report->priority).' priority',
            route('staff.maintenance.index'));

        return $technician;
    }

    public function notifyAssigned(User $user, string $title, string $body, string $url): void
    {
        // In-app only: people get many of these, email is kept for alerts.
        $user->notify(new OperationsAlert($title, $body, $url, 'assigned', email: false));
    }
}
