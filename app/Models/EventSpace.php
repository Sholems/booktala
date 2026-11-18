<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventSpace extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'capacity',
        'size',
        'hourly_rate',
        'daily_rate',
        'is_active',
        'amenities',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'size' => 'integer',
            'capacity' => 'integer',
            'hourly_rate' => 'decimal:2',
            'daily_rate' => 'decimal:2',
            'amenities' => 'array',
        ];
    }
}
