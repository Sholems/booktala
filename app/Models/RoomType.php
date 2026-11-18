<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class RoomType extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'base_price',
        'weekend_price',
        'price_per_week',
        'price_per_month',
        'max_adults',
        'max_children',
        'total_rooms',
        'bed_type',
        'room_size',
        'amenities',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'room_size' => 'integer',
            'max_adults' => 'integer',
            'max_children' => 'integer',
            'total_rooms' => 'integer',
            'base_price' => 'decimal:2',
            'weekend_price' => 'decimal:2',
            'price_per_week' => 'decimal:2',
            'price_per_month' => 'decimal:2',
            'amenities' => 'array',
        ];
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function availableRooms()
    {
        return $this->rooms()->where('status', 'available');
    }

    public function getAvailableRoomsCountAttribute()
    {
        return $this->rooms()->where('status', 'available')->count();
    }

    public function getOccupancyRateAttribute()
    {
        if ($this->total_rooms == 0) {
            return 0;
        }
        $occupied = $this->rooms()->where('status', 'occupied')->count();

        return round(($occupied / $this->total_rooms) * 100);
    }
}
