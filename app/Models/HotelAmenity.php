<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HotelAmenity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'category',
        'location',
        'opening_time',
        'closing_time',
        'is_active',
        'is_premium',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_premium' => 'boolean',
        ];
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(HotelAmenityReservation::class);
    }

    public static function categories()
    {
        return [
            'dining' => 'Dining',
            'wellness' => 'Wellness & Spa',
            'fitness' => 'Fitness',
            'recreation' => 'Recreation',
            'business' => 'Business Services',
            'other' => 'Other',
        ];
    }
}
