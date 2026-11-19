<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityMaintenanceSchedule extends Model
{
    use HasFactory;

    public const EQUIPMENT_GENERATOR = 'generator';

    public const EQUIPMENT_AC = 'ac';

    public const EQUIPMENT_PLUMBING = 'plumbing';

    public const EQUIPMENT_FUMIGATION = 'fumigation';

    public const EQUIPMENT_INVERTER = 'inverter';

    public const EQUIPMENT_BOREHOLE = 'borehole';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_PAUSED = 'paused';

    protected $fillable = [
        'site_id',
        'property_id',
        'equipment_type',
        'title',
        'frequency_days',
        'last_serviced_on',
        'next_due_on',
        'vendor_or_staff',
        'estimated_cost',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'last_serviced_on' => 'date',
            'next_due_on' => 'date',
            'frequency_days' => 'integer',
            'estimated_cost' => 'decimal:2',
        ];
    }

    public static function equipmentTypes(): array
    {
        return [
            self::EQUIPMENT_GENERATOR => 'Standby Diesel Generator',
            self::EQUIPMENT_AC => 'Air Conditioning (HVAC)',
            self::EQUIPMENT_PLUMBING => 'Plumbing & Water Treatment',
            self::EQUIPMENT_FUMIGATION => 'Pest Control & Fumigation',
            self::EQUIPMENT_INVERTER => 'Solar & Inverter Battery Bank',
            self::EQUIPMENT_BOREHOLE => 'Borehole & Water Pumps',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function isOverdue(): bool
    {
        return $this->next_due_on && $this->next_due_on->isPast();
    }

    public function isDueSoon(int $days = 7): bool
    {
        return $this->next_due_on && $this->next_due_on->isBetween(now(), now()->addDays($days));
    }

    /**
     * Mark maintenance routine completed and compute next recurring schedule.
     */
    public function recordServiceCompleted(?string $notes = null, ?float $actualCost = null): void
    {
        $nowDate = Carbon::today();
        $nextDue = Carbon::today()->addDays($this->frequency_days ?: 30);

        $appendNote = $notes ? "\n[Completed ".now()->format('Y-m-d').']: '.$notes : '';

        $this->update([
            'last_serviced_on' => $nowDate,
            'next_due_on' => $nextDue,
            'status' => self::STATUS_ACTIVE,
            'notes' => ($this->notes ?? '').$appendNote,
            'estimated_cost' => $actualCost !== null ? $actualCost : $this->estimated_cost,
        ]);
    }
}
