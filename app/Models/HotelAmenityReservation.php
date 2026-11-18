<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelAmenityReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_amenity_id',
        'user_id',
        'reservation_id',
        'booking_date',
        'start_time',
        'end_time',
        'guests',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

    public function amenity(): BelongsTo
    {
        return $this->belongsTo(HotelAmenity::class, 'hotel_amenity_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
