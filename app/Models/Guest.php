<?php

namespace App\Models;

use App\Services\GuestService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guest extends Model
{
    protected $fillable = ['user_id', 'name', 'email', 'normalized_email', 'phone', 'normalized_phone', 'status'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Guest $guest) {
            $guest->normalized_email = GuestService::normalizeEmail($guest->email);
            $guest->normalized_phone = GuestService::normalizePhone($guest->phone);
        });
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public static function forUser(User $user): self
    {
        return static::firstOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $user->name,
                'email' => $user->email,
                'normalized_email' => GuestService::normalizeEmail($user->email),
                'phone' => $user->phone,
                'normalized_phone' => GuestService::normalizePhone($user->phone),
                'status' => 'active',
            ],
        );
    }
}
