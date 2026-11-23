<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteMessage extends Model
{
    public const CATEGORIES = [
        'general' => ['label' => 'General Notice', 'color' => 'bg-slate-100 text-slate-700 border-slate-200', 'icon' => 'fa-bullhorn'],
        'urgent' => ['label' => 'Urgent Priority', 'color' => 'bg-red-100 text-red-800 border-red-300', 'icon' => 'fa-exclamation-triangle'],
        'guest_request' => ['label' => 'Guest Request', 'color' => 'bg-blue-100 text-blue-800 border-blue-200', 'icon' => 'fa-bell-concierge'],
        'maintenance' => ['label' => 'Maintenance Fault', 'color' => 'bg-amber-100 text-amber-800 border-amber-200', 'icon' => 'fa-wrench'],
        'handover' => ['label' => 'Shift Handover', 'color' => 'bg-purple-100 text-purple-800 border-purple-200', 'icon' => 'fa-handshake'],
        'security' => ['label' => 'Security Alert', 'color' => 'bg-emerald-100 text-emerald-800 border-emerald-200', 'icon' => 'fa-shield-halved'],
    ];

    protected $fillable = [
        'site_id',
        'user_id',
        'unit_id',
        'category',
        'message',
        'pinned',
        'pinned_at',
        'pinned_by',
    ];

    protected function casts(): array
    {
        return [
            'pinned' => 'boolean',
            'pinned_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function pinnedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pinned_by');
    }

    public function categoryMeta(): array
    {
        return self::CATEGORIES[$this->category] ?? self::CATEGORIES['general'];
    }

    public function scopeForSite(Builder $query, int $siteId): Builder
    {
        return $query->where('site_id', $siteId);
    }

    public function scopeByCategory(Builder $query, ?string $category): Builder
    {
        if (! $category || $category === 'all') {
            return $query;
        }

        return $query->where('category', $category);
    }
}
