<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventBooking extends Model
{
    protected $fillable = [
        'event_space_id',
        'user_id',
        'event_name',
        'event_type',
        'event_date',
        'start_time',
        'end_time',
        'num_guests',
        'total_price',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'total_price' => 'decimal:2',
        ];
    }

    public function eventSpace(): BelongsTo
    {
        return $this->belongsTo(EventSpace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function eventTypes()
    {
        return [
            'wedding' => 'Wedding',
            'birthday' => 'Birthday Party',
            'corporate' => 'Corporate Event',
            'conference' => 'Conference',
            'seminar' => 'Seminar/Workshop',
            'reunion' => 'Family Reunion',
            'other' => 'Other',
        ];
    }

    public static function statuses()
    {
        return [
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
        ];
    }
}
