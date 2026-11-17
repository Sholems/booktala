<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HousekeepingTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'assigned_to',
        'task_type',
        'priority',
        'status',
        'scheduled_date',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public static function taskTypes()
    {
        return [
            'cleaning' => 'Standard Cleaning',
            'deep_cleaning' => 'Deep Cleaning',
            'turnover' => 'Turnover',
            'inspection' => 'Room Inspection',
            'laundry' => 'Laundry',
            'amenity_restock' => 'Amenity Restock',
        ];
    }

    public static function priorities()
    {
        return [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'urgent' => 'Urgent',
        ];
    }

    public static function statuses()
    {
        return [
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'verified' => 'Verified',
        ];
    }
}