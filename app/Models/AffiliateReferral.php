<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateReferral extends Model
{
    protected $fillable = [
        'affiliate_id',
        'reservation_id',
        'guest_user_id',
        'base_accommodation_amount',
        'commission_rate',
        'commission_amount',
        'status',
        'check_in',
        'check_out',
        'matured_at',
        'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'base_accommodation_amount' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'check_in' => 'date',
            'check_out' => 'date',
            'matured_at' => 'datetime',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guest_user_id');
    }
}
