<?php

namespace App\Services;

use App\Models\MaintenanceReport;
use App\Models\StaffSchedule;
use App\Models\Task;
use App\Models\User;
use App\Notifications\OperationsAlert;
use App\Support\Supervision;
use Illuminate\Support\Collection;

/**
 * The every-15-minutes operations check:
 *
 *  - work nobody has started within its time limit goes to the site's
 *    supervisor (step 1), and if still untouched after the same time again,
 *    to managers and head office (step 2);
 *  - a scheduled shift with no clock-in 30 minutes after it started is
 *    reported to the site's supervisor;
 *  - shifts nobody clocked out of are closed.
 */
class EscalationService
{
    /** Minutes allowed before unstarted work escalates, by priority. */
    public const LIMITS = [
        'urgent' => 60,
        'emergency' => 60,
        'high' => 240,
        'medium' => 1440,
        'low' => 2880,
    ];

    public const MISSED_SHIFT_GRACE_MINUTES = 30;

    public function __construct(private AttendanceService $attendance)
    {
    }

    /**
     * @return array{tasks: int, maintenance: int, missed_shifts: int, closed_attendance: int}
     */
    public function run(): array
    {
        return [
            'tasks' => $this->escalateTasks(),
            'maintenance' => $this->escalateMaintenance(),
            'missed_shifts' => $this->reportMissedShifts(),
            'closed_attendance' => $this->attendance->closeForgotten(),
        ];
    }

    public static function limitFor(?string $priority): int
    {
        return self::LIMITS[$priority] ?? self::LIMITS['medium'];
    }

    private function escalateTasks(): int
    {
        $count = 0;
        $tasks = Task::where('status', 'pending')
            ->whereDate('due_date', '<=', today())
            ->where('escalation_level', '<', 2)
            ->with(['property.site', 'assignedTo'])
            ->get();

        foreach ($tasks as $task) {
            $step = $this->dueStep($task->escalation_level, $task->assigned_at ?? $task->created_at, $task->escalated_at, $task->priority);
            if (!$step || !$task->property?->site_id) {
                continue;
            }

            $who = $task->assignedTo ? 'assigned to ' . $task->assignedTo->name : 'nobody assigned';
            $this->alert(
                $task->property->site_id,
                $step,
                ($step === 1 ? 'Not started: ' : 'Still not started: ') . $task->title,
                $task->property->title . ' (' . $task->property->site->name . ') · ' . ucfirst($task->priority) . ' priority · ' . $who . '.',
                staffUrl: route('staff.team.index'),
                adminUrl: route('admin.tasks.show', $task),
            );
            $task->forceFill(['escalation_level' => $step, 'escalated_at' => now()])->saveQuietly();
            $count++;
        }

        return $count;
    }

    private function escalateMaintenance(): int
    {
        $count = 0;
        $reports = MaintenanceReport::where('status', 'pending')
            ->where('escalation_level', '<', 2)
            ->with(['property.site', 'assignedTo'])
            ->get();

        foreach ($reports as $report) {
            $step = $this->dueStep($report->escalation_level, $report->assigned_at ?? $report->created_at, $report->escalated_at, $report->priority);
            if (!$step || !$report->property?->site_id) {
                continue;
            }

            $who = $report->assignedTo ? 'assigned to ' . $report->assignedTo->name : 'nobody assigned';
            $this->alert(
                $report->property->site_id,
                $step,
                ($step === 1 ? 'Repair not started: ' : 'Repair still not started: ') . $report->title,
                $report->property->title . ' (' . $report->property->site->name . ') · ' . ucfirst($report->priority) . ' priority · ' . $who . '.',
                staffUrl: route('staff.team.index'),
                adminUrl: route('admin.maintenance.index'),
            );
            $report->forceFill(['escalation_level' => $step, 'escalated_at' => now()])->saveQuietly();
            $count++;
        }

        return $count;
    }

    /**
     * Which escalation step is now due, if any.
     */
    private function dueStep(int $level, $clockStart, $escalatedAt, ?string $priority): ?int
    {
        $limit = self::limitFor($priority);

        if ($level === 0 && $clockStart && $clockStart->lte(now()->subMinutes($limit))) {
            return 1;
        }
        if ($level === 1 && $escalatedAt && $escalatedAt->lte(now()->subMinutes($limit))) {
            return 2;
        }

        return null;
    }

    private function reportMissedShifts(): int
    {
        $count = 0;
        $shifts = StaffSchedule::where('status', 'scheduled')
            ->whereNull('missed_alerted_at')
            ->whereNotNull('site_id')
            ->where(fn ($q) => $q->whereDate('date', today())->orWhereDate('date', today()->subDay()))
            ->with(['staff.user', 'site'])
            ->get();

        foreach ($shifts as $shift) {
            if (now()->lt($shift->startsAt()->addMinutes(self::MISSED_SHIFT_GRACE_MINUTES))) {
                continue;
            }
            $clockedIn = $shift->staff->attendances()
                ->where('site_id', $shift->site_id)
                ->where('clock_in_at', '>=', $shift->startsAt()->subHours(2))
                ->exists();
            if ($clockedIn) {
                continue;
            }

            $name = $shift->staff->user->name ?? 'A staff member';
            $this->alert(
                $shift->site_id,
                1,
                'No clock-in: ' . $name . ' at ' . $shift->site->name,
                $name . ' was due at ' . $shift->startsAt()->format('g:i A') . ' (' . (StaffSchedule::shiftTypes()[$shift->shift_type] ?? $shift->shift_type) . ' shift) and has not clocked in. Arrange cover if needed.',
                staffUrl: route('staff.team.index'),
                adminUrl: route('admin.attendance.index', ['site_id' => $shift->site_id]),
            );
            $shift->forceFill(['missed_alerted_at' => now()])->saveQuietly();
            $count++;
        }

        return $count;
    }

    private function alert(int $siteId, int $step, string $title, string $body, string $staffUrl, string $adminUrl): void
    {
        /** @var Collection<int, User> $recipients */
        $recipients = Supervision::alertRecipients($siteId, $step);

        foreach ($recipients as $user) {
            $isHeadOffice = $user->hasAnyRole(['super_admin', 'admin']);
            $user->notify(new OperationsAlert($title, $body, $isHeadOffice ? $adminUrl : $staffUrl, 'escalated'));
        }
    }
}
