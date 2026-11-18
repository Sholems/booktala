<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StaffSchedule extends Model
{
    protected $fillable = [
        'staff_id',
        'site_id',
        'date',
        'shift_type',
        'start_time',
        'end_time',
        'notes',
        'status',
        'created_by',
        'missed_alerted_at',
    ];

    /** Start and end times for each shift type. Night shifts end the next morning. */
    public const SHIFT_TIMES = [
        'morning' => ['06:00', '14:00'],
        'afternoon' => ['14:00', '22:00'],
        'night' => ['22:00', '06:00'],
        'day' => ['08:00', '18:00'],
        'night_shift' => ['20:00', '08:00'],
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'missed_alerted_at' => 'datetime',
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

    public function attendance(): HasOne
    {
        return $this->hasOne(Attendance::class)->latestOfMany('clock_in_at');
    }

    public function startsAt(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->date->format('Y-m-d').' '.Carbon::parse($this->start_time)->format('H:i'));
    }

    public function endsAt(): CarbonImmutable
    {
        $end = CarbonImmutable::parse($this->date->format('Y-m-d').' '.Carbon::parse($this->end_time)->format('H:i'));

        return $end->lte($this->startsAt()) ? $end->addDay() : $end;
    }

    public function label(): string
    {
        return (self::shiftTypes()[$this->shift_type] ?? $this->shift_type)
            .' ('.$this->startsAt()->format('g:i A').' – '.$this->endsAt()->format('g:i A').')';
    }

    public static function shiftTypes()
    {
        return [
            'morning' => 'Morning',
            'afternoon' => 'Afternoon',
            'night' => 'Night',
            'day' => 'Day',
            'night_shift' => 'Night (long)',
        ];
    }
}
