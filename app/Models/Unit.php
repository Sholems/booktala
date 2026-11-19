<?php

namespace App\Models;

use App\Support\PropertyAccess;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Unit extends Model implements HasMedia
{
    use HasFactory, HasSlug, InteractsWithMedia, LogsActivity;

    protected $table = 'properties';

    protected $fillable = [
        'unit_code',
        'unit_type',
        'title',
        'slug',
        'description',
        'short_description',
        'address',
        'city',
        'state',
        'country',
        'latitude',
        'longitude',
        'price_per_night',
        'weekend_price',
        'price_per_week',
        'price_per_month',
        'min_nights',
        'max_nights',
        'max_guests',
        'bedrooms',
        'bathrooms',
        'toilets',
        'status',
        'is_featured',
        'check_in_time',
        'check_out_time',
        'house_rules',
        'cancellation_policy',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'created_by',
        'owner_id',
        'site_id',
        'wifi_ssid',
        'wifi_password',
        'door_access_code',
        'checkin_instructions',
    ];

    protected static function booted(): void
    {
        static::creating(function (Unit $unit) {
            $unit->unit_code ??= 'UNIT-'.Str::upper((string) Str::ulid());
            $unit->owner_id ??= Owner::company()->id;
            $unit->site_id ??= Site::create([
                'name' => $unit->title,
                'address' => $unit->address,
                'city' => $unit->city,
                'state' => $unit->state,
                'country' => $unit->country ?? 'Nigeria',
            ])->id;
        });
    }

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'price_per_night' => 'decimal:2',
            'weekend_price' => 'decimal:2',
            'price_per_week' => 'decimal:2',
            'price_per_month' => 'decimal:2',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    public function getMorphClass(): string
    {
        return Property::class;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'property_amenities', 'property_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class, 'property_id')->orderBy('sort_order');
    }

    public function primaryImage()
    {
        return $this->hasOne(PropertyImage::class, 'property_id')->where('is_primary', true);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'property_id');
    }

    public function managementAgreements(): HasMany
    {
        return $this->hasMany(ManagementAgreement::class, 'unit_id');
    }

    public function financeEntries(): HasMany
    {
        return $this->hasMany(FinanceEntry::class, 'unit_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'property_id');
    }

    public function blockedDates(): HasMany
    {
        return $this->hasMany(BlockedDate::class, 'property_id');
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class, 'property_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'property_id');
    }

    public function maintenanceReports(): HasMany
    {
        return $this->hasMany(MaintenanceReport::class, 'property_id');
    }

    public function legacyRooms(): HasMany
    {
        return $this->hasMany(Room::class, 'unit_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeVisibleTo($query, User $user)
    {
        return PropertyAccess::scope($query, $user, 'properties.id');
    }

    public function getNameAttribute(): ?string
    {
        return $this->title;
    }

    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('overall_rating') ?: 0;
    }

    public function getReviewCountAttribute()
    {
        return $this->reviews()->count();
    }
}
