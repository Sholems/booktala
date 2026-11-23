<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One clock-in to clock-out period worked at a site. Location is recorded and
 * flagged (on site / off site / unknown) rather than blocking, because phone
 * GPS is imprecise indoors; supervisors review the flags.
 */
class Attendance extends Model
{
    /** Minutes after shift start before a clock-in counts as late. */
    public const LATE_GRACE_MINUTES = 10;

    protected $fillable = [
        'staff_id', 'site_id', 'staff_schedule_id',
        'clock_in_at', 'clock_in_lat', 'clock_in_lng', 'clock_in_accuracy_m', 'clock_in_distance_m', 'clock_in_location', 'late_minutes',
        'clock_out_at', 'clock_out_lat', 'clock_out_lng', 'clock_out_distance_m', 'clock_out_location',
        'worked_minutes', 'handover_note', 'auto_closed',
        'handover_acknowledged_by', 'handover_acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'clock_in_at' => 'datetime',
            'clock_out_at' => 'datetime',
            'handover_acknowledged_at' => 'datetime',
            'auto_closed' => 'boolean',
            'late_minutes' => 'integer',
            'worked_minutes' => 'integer',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(StaffSchedule::class, 'staff_schedule_id');
    }

    public function handoverAcknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handover_acknowledged_by');
    }

    public function isHandoverAcknowledged(): bool
    {
        return $this->handover_acknowledged_at !== null;
    }

    public function isOpen(): bool
    {
        return $this->clock_out_at === null;
    }

    public function isLate(): bool
    {
        return $this->late_minutes > self::LATE_GRACE_MINUTES;
    }

    /** Things a supervisor should look at. */
    public function flags(): array
    {
        $flags = [];
        if ($this->isLate()) {
            $flags[] = 'Late '.$this->late_minutes.' min';
        }
        if ($this->clock_in_location === 'off_site') {
            $flags[] = 'Clocked in '.number_format((int) $this->clock_in_distance_m).' m away';
        }
        if ($this->clock_in_location === 'unknown') {
            $flags[] = 'No location';
        }
        if ($this->clock_out_location === 'off_site') {
            $flags[] = 'Clocked out off site';
        }
        if ($this->auto_closed) {
            $flags[] = 'Forgot to clock out';
        }
        if (! $this->staff_schedule_id) {
            $flags[] = 'No shift scheduled';
        }

        return $flags;
    }

    public function workedLabel(): string
    {
        $minutes = $this->worked_minutes ?? (int) $this->clock_in_at->diffInMinutes(now());

        return intdiv($minutes, 60).'h '.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT).'m';
    }
}
