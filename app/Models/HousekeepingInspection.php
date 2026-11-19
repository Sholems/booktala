<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HousekeepingInspection extends Model
{
    use HasFactory;

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'task_id',
        'unit_id',
        'cleaner_id',
        'supervisor_id',
        'status',
        'checklist',
        'before_photos',
        'after_photos',
        'cleaner_notes',
        'supervisor_notes',
        'submitted_at',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'checklist' => 'array',
            'before_photos' => 'array',
            'after_photos' => 'array',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function cleaner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cleaner_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Default inspection checklist items.
     */
    public static function defaultChecklist(): array
    {
        return [
            [
                'section' => 'Bedroom & Linens',
                'items' => [
                    ['key' => 'bed_linen', 'label' => 'Bed sheets, duvet and pillowcases fresh & wrinkle-free', 'passed' => true],
                    ['key' => 'mattress_protector', 'label' => 'Clean mattress protector inspected', 'passed' => true],
                    ['key' => 'wardrobe_hangers', 'label' => 'Wardrobe wiped, hangers & safe operational', 'passed' => true],
                    ['key' => 'bedside_lamps', 'label' => 'Bedside lamps and charging ports functional', 'passed' => true],
                ],
            ],
            [
                'section' => 'Bathroom & Amenities',
                'items' => [
                    ['key' => 'towels_replenished', 'label' => 'Fresh bath, hand and face towels folded neatly', 'passed' => true],
                    ['key' => 'toiletries_restocked', 'label' => 'Luxury shampoo, body wash, dental kit restocked', 'passed' => true],
                    ['key' => 'sanitary_fixtures', 'label' => 'Toilet, sink and shower sanitized and polished', 'passed' => true],
                    ['key' => 'water_pressure_heater', 'label' => 'Water pressure, heater and drainage verified', 'passed' => true],
                ],
            ],
            [
                'section' => 'Living Area & Appliances',
                'items' => [
                    ['key' => 'dusting_surfaces', 'label' => 'All tables, TV consoles, and shelves dust-free', 'passed' => true],
                    ['key' => 'ac_cooling', 'label' => 'AC operational, remotes tested, filters clear', 'passed' => true],
                    ['key' => 'tv_wifi_connectivity', 'label' => 'Smart TV and Wi-Fi connection verified', 'passed' => true],
                    ['key' => 'kitchenette_fridge', 'label' => 'Fridge emptied, wiped clean; kettle & mugs sanitized', 'passed' => true],
                ],
            ],
            [
                'section' => 'General Readiness',
                'items' => [
                    ['key' => 'trash_bins', 'label' => 'All waste bins emptied and lined with fresh bags', 'passed' => true],
                    ['key' => 'floor_cleanliness', 'label' => 'Floors swept, vacuumed, and freshly mopped', 'passed' => true],
                    ['key' => 'windows_locks', 'label' => 'Windows and sliding doors securely latched', 'passed' => true],
                    ['key' => 'scent_ambience', 'label' => 'Room deodorized with fresh welcoming fragrance', 'passed' => true],
                ],
            ],
        ];
    }

    /**
     * Approve inspection: marks task complete and switches unit from cleaning to available.
     */
    public function approve(User $supervisor, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'supervisor_id' => $supervisor->id,
            'supervisor_notes' => $notes,
            'reviewed_at' => now(),
        ]);

        if ($this->task) {
            $this->task->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }

        // Transition unit from 'cleaning' to 'available'
        if ($this->unit) {
            $this->unit->update([
                'status' => 'available',
            ]);
        }
    }

    /**
     * Reject inspection: sends back for rework, unit remains in 'cleaning'.
     */
    public function reject(User $supervisor, string $notes): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'supervisor_id' => $supervisor->id,
            'supervisor_notes' => $notes,
            'reviewed_at' => now(),
        ]);

        if ($this->task) {
            $this->task->update([
                'status' => 'in_progress',
                'notes' => ($this->task->notes ? $this->task->notes."\n" : '').'Supervisor Inspection Feedback: '.$notes,
            ]);
        }

        if ($this->unit) {
            $this->unit->update([
                'status' => 'cleaning',
            ]);
        }
    }
}
