<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Affiliate extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'commission_rate',
        'bank_code',
        'bank_name',
        'account_number',
        'account_name',
        'recipient_code',
        'status',
        'lifetime_earnings',
        'current_balance',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'lifetime_earnings' => 'decimal:2',
            'current_balance' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(AffiliateReferral::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(AffiliatePayout::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(AffiliateClick::class);
    }

    public function getPendingBalanceAttribute(): float
    {
        return (float) $this->referrals()->where('status', 'pending')->sum('commission_amount');
    }

    public function getCompletedReferralsCountAttribute(): int
    {
        return $this->referrals()->whereIn('status', ['approved', 'paid'])->count();
    }

    public function getReferralUrlAttribute(): string
    {
        return url('/?ref=' . urlencode($this->code));
    }

    public static function generateUniqueCode(string $name): string
    {
        $base = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', Str::slug($name, '')));
        if (strlen($base) < 3) {
            $base = 'PARTNER';
        }
        $code = substr($base, 0, 10);
        
        $candidate = $code;
        $counter = 1;
        while (self::where('code', $candidate)->exists()) {
            $candidate = $code . $counter++;
        }

        return $candidate;
    }
}
