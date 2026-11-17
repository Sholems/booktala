<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestCheckin extends Model
{
    protected $fillable = [
        'reservation_id',
        'check_in_date',
        'check_in_time',
        'guest_first_name',
        'guest_last_name',
        'guest_email',
        'guest_phone',
        'arrival_flight',
        'arrival_time',
        'special_requests',
        'id_type',
        'id_number',
        'id_document_path',
        'num_adults',
        'num_children',
        'vehicle_plate',
        'emergency_contact_name',
        'emergency_contact_phone',
        'status',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'check_in_time' => 'datetime:H:i',
            'verified_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public static function idTypes()
    {
        return [
            'passport' => 'Passport',
            'national_id' => 'National ID',
            'drivers_license' => "Driver's License",
            'voters_card' => "Voter's Card",
            'other' => 'Other',
        ];
    }

    public static function statuses()
    {
        return [
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    }
}