<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Something that happened at a site that needs a record: theft, damage, a
 * medical emergency, a power failure. High and critical incidents alert
 * supervisors and head office immediately.
 */
class Incident extends Model
{
    use \App\Traits\VisibleBySite;

    public const CATEGORIES = [
        'theft' => 'Theft / burglary',
        'trespass' => 'Trespass / suspicious person',
        'damage' => 'Damage to property',
        'dispute' => 'Guest dispute / noise',
        'medical' => 'Medical',
        'fire' => 'Fire / smoke',
        'power' => 'Power failure',
        'water' => 'Water / flooding',
        'other' => 'Other',
    ];

    public const SEVERITIES = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'];

    protected $fillable = [
        'site_id', 'property_id', 'reported_by', 'occurred_at', 'category', 'severity', 'title', 'description',
        'actions_taken', 'photo_path', 'status', 'resolved_by', 'resolved_at', 'resolution',
    ];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime', 'resolved_at' => 'datetime'];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isSerious(): bool
    {
        return in_array($this->severity, ['high', 'critical'], true);
    }

    public function severityBadge(): string
    {
        return [
            'low' => 'bg-gray-100 text-gray-700',
            'medium' => 'bg-yellow-100 text-yellow-800',
            'high' => 'bg-orange-100 text-orange-800',
            'critical' => 'bg-red-100 text-red-800',
        ][$this->severity] ?? 'bg-gray-100 text-gray-700';
    }
}
