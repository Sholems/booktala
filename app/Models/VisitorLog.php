<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A visitor signed in at the gate or front desk, and signed out when they leave.
 * Only the type of ID shown is kept, never the number.
 */
class VisitorLog extends Model
{
    use \App\Traits\VisibleBySite;

    public const PURPOSES = [
        'guest_visit' => 'Visiting a guest',
        'delivery' => 'Delivery',
        'contractor' => 'Contractor / repair',
        'viewing' => 'Property viewing',
        'staff' => 'Staff / agency',
        'other' => 'Other',
    ];

    public const ID_TYPES = ['nin' => 'NIN slip / card', 'drivers_licence' => "Driver's licence", 'passport' => 'Passport', 'voters_card' => "Voter's card", 'work_id' => 'Work ID', 'none' => 'None shown'];

    protected $fillable = [
        'site_id', 'property_id', 'visitor_name', 'phone', 'purpose', 'host', 'vehicle_plate', 'id_type',
        'arrived_at', 'left_at', 'logged_by', 'signed_out_by', 'notes',
    ];

    protected function casts(): array
    {
        return ['arrived_at' => 'datetime', 'left_at' => 'datetime'];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function loggedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    public function scopeOnSite($query)
    {
        return $query->whereNull('left_at');
    }
}
