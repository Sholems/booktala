<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratorFuelLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'generator_name',
        'run_hours_start',
        'run_hours_end',
        'hours_run',
        'litres_added',
        'diesel_cost',
        'fuel_level_before_percent',
        'fuel_level_after_percent',
        'logged_by',
        'logged_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'run_hours_start' => 'decimal:2',
            'run_hours_end' => 'decimal:2',
            'hours_run' => 'decimal:2',
            'litres_added' => 'decimal:2',
            'diesel_cost' => 'decimal:2',
            'logged_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (GeneratorFuelLog $log) {
            if ($log->run_hours_end >= $log->run_hours_start && ! $log->hours_run) {
                $log->hours_run = max(0, (float) $log->run_hours_end - (float) $log->run_hours_start);
            }
        });
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function loggedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    /**
     * Litres consumed per run hour (fuel efficiency).
     */
    public function getLitresPerHourAttribute(): float
    {
        if ((float) $this->hours_run <= 0) {
            return 0.0;
        }

        return round((float) $this->litres_added / (float) $this->hours_run, 2);
    }
}
