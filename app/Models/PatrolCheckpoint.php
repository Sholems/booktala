<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * A point guards must visit on patrol (gate, car park, generator house...).
 * A QR code holding its token is printed and fixed there; scanning it with a
 * phone records the visit.
 */
class PatrolCheckpoint extends Model
{
    use \App\Traits\VisibleBySite;

    protected $fillable = ['site_id', 'name', 'token', 'is_active', 'missed_alerted_at'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'missed_alerted_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (PatrolCheckpoint $checkpoint) {
            $checkpoint->token ??= Str::random(32);
        });
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function scans(): HasMany
    {
        return $this->hasMany(PatrolScan::class);
    }

    public function lastScan(): HasOne
    {
        return $this->hasOne(PatrolScan::class)->latestOfMany('scanned_at');
    }

    public function scanUrl(): string
    {
        return route('staff.patrol.scan', $this->token);
    }
}
