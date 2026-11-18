<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuestProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'guest_id',
        'total_stays',
        'total_nights',
        'total_spent',
        'loyalty_points',
        'loyalty_tier',
        'last_stay_date',
        'preferences',
        'special_requests',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_stays' => 'integer',
            'total_nights' => 'integer',
            'total_spent' => 'decimal:2',
            'loyalty_points' => 'integer',
            'last_stay_date' => 'date',
            'preferences' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'guest_id', 'guest_id');
    }

    public static function tiers()
    {
        return [
            'bronze' => ['name' => 'Bronze', 'min_points' => 0, 'discount' => 0],
            'silver' => ['name' => 'Silver', 'min_points' => 1000, 'discount' => 5],
            'gold' => ['name' => 'Gold', 'min_points' => 5000, 'discount' => 10],
            'platinum' => ['name' => 'Platinum', 'min_points' => 15000, 'discount' => 15],
        ];
    }

    public function getTierAttribute()
    {
        $tiers = self::tiers();
        foreach (array_reverse($tiers) as $key => $tier) {
            if ($this->loyalty_points >= $tier['min_points']) {
                return $key;
            }
        }

        return 'bronze';
    }

    public function getDiscountAttribute()
    {
        return self::tiers()[$this->tier]['discount'] ?? 0;
    }

    public function addPoints(int $points)
    {
        $this->increment('loyalty_points', $points);
        $this->updateTier();
    }

    public function updateTier()
    {
        $tiers = self::tiers();
        foreach (array_reverse($tiers) as $key => $tier) {
            if ($this->loyalty_points >= $tier['min_points']) {
                if ($this->loyalty_tier !== $key) {
                    $this->update(['loyalty_tier' => $key]);
                }

                return;
            }
        }
    }
}
