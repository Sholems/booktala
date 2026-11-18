<?php

namespace App\Services;

use App\Models\Site;
use App\Models\Staff;
use App\Models\StaffSchedule;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Planning shifts at a site, shared by head office and site supervisors.
 */
class RosterService
{
    public function schedule(Staff $staff, Site $site, string $date, string $shiftType, ?string $notes, ?User $by): StaffSchedule
    {
        if (! array_key_exists($shiftType, StaffSchedule::SHIFT_TIMES)) {
            throw ValidationException::withMessages(['shift_type' => 'Choose a valid shift.']);
        }

        $worksThere = $staff->assignments()
            ->where('site_id', $site->id)
            ->whereDate('starts_on', '<=', $date)
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $date))
            ->exists();
        if (! $worksThere) {
            throw ValidationException::withMessages(['staff_id' => ($staff->user->name ?? 'This staff member').' is not assigned to '.$site->name.' on that date.']);
        }

        [$start, $end] = StaffSchedule::SHIFT_TIMES[$shiftType];

        // One shift per person per day; planning again replaces it.
        $existing = $staff->schedules()->whereDate('date', $date)->first();

        $attributes = [
            'site_id' => $site->id,
            'shift_type' => $shiftType,
            'start_time' => $start,
            'end_time' => $end,
            'notes' => $notes,
            'status' => 'scheduled',
            'created_by' => $by?->id,
            'missed_alerted_at' => null,
        ];

        if ($existing) {
            $existing->update($attributes);

            return $existing;
        }

        return $staff->schedules()->create($attributes + ['date' => $date]);
    }
}
