<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'reservation_id',
        'user_id',
        'guest_id',
        'amount',
        'currency',
        'paystack_reference',
        'paystack_status',
        'payment_method',
        'type',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            $payment->guest_id ??= $payment->reservation?->guest_id;
        });
    }
}
