<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Site;
use App\Models\Staff;
use App\Models\StaffSchedule;
use Illuminate\Validation\ValidationException;

/**
 * Clocking in and out. Location is checked against the site's geofence and
 * recorded, not enforced: a staff member with a bad GPS signal can still clock
 * in, and the supervisor sees the flag.
 */
class AttendanceService
{
    /** Shifts open longer than this are closed by the scheduler and flagged. */
    public const MAX_OPEN_HOURS = 16;

    public function clockIn(Staff $staff, Site $site, ?float $lat, ?float $lng, ?int $accuracy): Attendance
    {
        if ($staff->openAttendance()->exists()) {
            throw ValidationException::withMessages(['site_id' => 'You are already clocked in. Clock out first.']);
        }
        if (! $staff->activeAssignments()->where('site_id', $site->id)->exists()) {
            throw ValidationException::withMessages(['site_id' => 'You are not assigned to '.$site->name.'.']);
        }

        $schedule = $this->shiftFor($staff, $site);
        [$distance, $location] = $this->locate($site, $lat, $lng, $accuracy);

        $late = 0;
        if ($schedule && now()->gt($schedule->startsAt())) {
            $late = (int) $schedule->startsAt()->diffInMinutes(now());
        }

        return $staff->attendances()->create([
            'site_id' => $site->id,
            'staff_schedule_id' => $schedule?->id,
            'clock_in_at' => now(),
            'clock_in_lat' => $lat,
            'clock_in_lng' => $lng,
            'clock_in_accuracy_m' => $accuracy,
            'clock_in_distance_m' => $distance,
            'clock_in_location' => $location,
            'late_minutes' => $late,
        ]);
    }

    public function clockOut(Staff $staff, ?float $lat, ?float $lng, ?int $accuracy, ?string $handoverNote): Attendance
    {
        $attendance = $staff->openAttendance()->with('site', 'schedule')->first();
        if (! $attendance) {
            throw ValidationException::withMessages(['clock' => 'You are not clocked in.']);
        }

        [$distance, $location] = $this->locate($attendance->site, $lat, $lng, $accuracy);

        $attendance->update([
            'clock_out_at' => now(),
            'clock_out_lat' => $lat,
            'clock_out_lng' => $lng,
            'clock_out_distance_m' => $distance,
            'clock_out_location' => $location,
            'worked_minutes' => (int) $attendance->clock_in_at->diffInMinutes(now()),
            'handover_note' => $handoverNote !== null && trim($handoverNote) !== '' ? trim($handoverNote) : null,
        ]);

        $attendance->schedule?->update(['status' => 'completed']);

        return $attendance;
    }

    /**
     * Close shifts nobody clocked out of, counting time up to the planned end
     * (or MAX_OPEN_HOURS when there was no plan).
     */
    public function closeForgotten(): int
    {
        $closed = 0;
        $stale = Attendance::whereNull('clock_out_at')
            ->where('clock_in_at', '<', now()->subHours(self::MAX_OPEN_HOURS))
            ->with('schedule')
            ->get();

        foreach ($stale as $attendance) {
            $end = $attendance->schedule?->endsAt() ?? $attendance->clock_in_at->copy()->addHours(self::MAX_OPEN_HOURS);
            if ($end->lte($attendance->clock_in_at)) {
                $end = $attendance->clock_in_at;
            }
            $attendance->update([
                'clock_out_at' => $end,
                'worked_minutes' => (int) $attendance->clock_in_at->diffInMinutes($end),
                'auto_closed' => true,
            ]);
            $attendance->schedule?->update(['status' => 'completed']);
            $closed++;
        }

        return $closed;
    }

    /**
     * Today's shift at this site, or last night's overnight shift that is still running.
     */
    public function shiftFor(Staff $staff, Site $site): ?StaffSchedule
    {
        return $staff->schedules()
            ->where(fn ($q) => $q->whereDate('date', today())->orWhereDate('date', today()->subDay()))
            ->where('status', 'scheduled')
            ->where(fn ($q) => $q->whereNull('site_id')->orWhere('site_id', $site->id))
            ->orderByDesc('date')
            ->get()
            ->first(fn (StaffSchedule $s) => $s->date->isToday() || now()->lt($s->endsAt()));
    }

    /**
     * @return array{0: ?int, 1: string} distance in metres and on_site|off_site|unknown
     */
    private function locate(Site $site, ?float $lat, ?float $lng, ?int $accuracy): array
    {
        if ($lat === null || $lng === null || ! $site->hasLocation()) {
            return [null, 'unknown'];
        }

        $distance = $site->distanceTo($lat, $lng);
        // Allow for the phone's own uncertainty, up to 100 m.
        $allowed = $site->geofence_radius_m + min((int) $accuracy, 100);

        return [$distance, $distance <= $allowed ? 'on_site' : 'off_site'];
    }
}
