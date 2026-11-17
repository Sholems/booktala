<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A staff member working a position at a site for a period. Ending an
 * assignment (ends_on) keeps the history; it is never deleted on transfer.
 */
class StaffAssignment extends Model
{
    protected $fillable = ['staff_id', 'site_id', 'position_id', 'is_primary', 'starts_on', 'ends_on'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean', 'starts_on' => 'date', 'ends_on' => 'date'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Assignments in force today.
     */
    public function scopeActive(Builder $query): Builder
    {
        $today = today()->toDateString();

        return $query->whereDate('starts_on', '<=', $today)
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $today));
    }

    public function isActive(): bool
    {
        return $this->starts_on->lte(today()) && ($this->ends_on === null || $this->ends_on->gte(today()));
    }
}
