<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use \App\Traits\VisibleByProperty;

    protected $fillable = [
        'title',
        'description',
        'property_id',
        'reservation_id',
        'assigned_to',
        'assigned_by',
        'type',
        'priority',
        'status',
        'due_date',
        'completed_at',
        'started_at',
        'assigned_at',
        'escalation_level',
        'escalated_at',
        'notes',
    ];

    protected static function booted(): void
    {
        static::saving(function (Task $task) {
            if ($task->isDirty('assigned_to')) {
                $task->assigned_at = $task->assigned_to ? now() : null;
            }
            if ($task->isDirty('status') && $task->status === 'in_progress' && !$task->started_at) {
                $task->started_at = now();
            }
            // A new person or a start resets the escalation clock.
            if ($task->exists && ($task->isDirty('assigned_to') || ($task->isDirty('status') && $task->status !== 'pending'))) {
                $task->escalation_level = 0;
                $task->escalated_at = null;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'started_at' => 'datetime',
            'assigned_at' => 'datetime',
            'escalated_at' => 'datetime',
        ];
    }

    /** The note thread between the assigned staff member and admins, oldest first. */
    public function taskNotes(): HasMany
    {
        return $this->hasMany(TaskNote::class)->oldest()->oldest('id');
    }

    public function latestTaskNote(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TaskNote::class)->latestOfMany();
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function images(): HasMany
    {
        return $this->hasMany(TaskImage::class);
    }
}