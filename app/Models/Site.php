<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * A physical location staff work at: a building or estate with several
 * apartments, or a standalone apartment (a one-unit site).
 */
class Site extends Model
{
    use HasSlug;

    protected $fillable = ['name', 'slug', 'address', 'city', 'state', 'country', 'latitude', 'longitude', 'geofence_radius_m', 'phone', 'notes', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'latitude' => 'float', 'longitude' => 'float', 'geofence_radius_m' => 'integer'];
    }

    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Metres from the site to a point (haversine), or null if the site has no location.
     */
    public function distanceTo(float $lat, float $lng): ?int
    {
        if (!$this->hasLocation()) {
            return null;
        }

        $r = 6371000;
        $dLat = deg2rad($lat - $this->latitude);
        $dLng = deg2rad($lng - $this->longitude);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($this->latitude)) * cos(deg2rad($lat)) * sin($dLng / 2) ** 2;

        return (int) round($r * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(StaffSchedule::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug');
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(StaffAssignment::class);
    }

    public function activeAssignments(): HasMany
    {
        return $this->assignments()->active();
    }
}
