<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AffiliatePayout extends Model
{
    protected $fillable = [
        'affiliate_id',
        'payout_ref',
        'amount',
        'bank_name',
        'account_number',
        'account_name',
        'status',
        'transfer_reference',
        'processed_by',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public static function generatePayoutRef(): string
    {
        return 'PAY-'.strtoupper(Str::random(10));
    }
}
