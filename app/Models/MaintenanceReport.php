<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceReport extends Model
{
    use \App\Traits\VisibleByProperty;

    protected $fillable = [
        'property_id',
        'reported_by',
        'assigned_to',
        'title',
        'description',
        'priority',
        'status',
        'resolved_at',
        'resolution_notes',
        'assigned_at',
        'escalation_level',
        'escalated_at',
    ];

    protected static function booted(): void
    {
        static::saving(function (MaintenanceReport $report) {
            if ($report->isDirty('assigned_to')) {
                $report->assigned_at = $report->assigned_to ? now() : null;
            }
            if ($report->exists && ($report->isDirty('assigned_to') || ($report->isDirty('status') && $report->status !== 'pending'))) {
                $report->escalation_level = 0;
                $report->escalated_at = null;
            }
        });

        // New reports go straight to a maintenance technician at that site, if one is on.
        static::created(function (MaintenanceReport $report) {
            if (!$report->assigned_to) {
                app(\App\Services\WorkRouter::class)->routeMaintenance($report);
            }
        });
    }

    public const PRIORITIES = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'];
    public const STATUSES = ['pending' => 'Reported', 'in_progress' => 'In progress', 'resolved' => 'Resolved'];

    /**
     * Older forms send "emergency"; the database calls it "urgent".
     */
    public function setPriorityAttribute($value): void
    {
        $this->attributes['priority'] = $value === 'emergency' ? 'urgent' : $value;
    }

    public function isUrgent(): bool
    {
        return in_array($this->priority, ['urgent', 'emergency'], true);
    }

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime', 'assigned_at' => 'datetime', 'escalated_at' => 'datetime'];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}