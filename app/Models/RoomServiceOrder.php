<?php

namespace App\Models;

use App\Support\PropertyAccess;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomServiceOrder extends Model
{
    use HasFactory;

    /**
     * Orders whose booking is at a property this user may see.
     */
    public function scopeVisibleTo($query, User $user)
    {
        if (PropertyAccess::seesAll($user)) {
            return $query;
        }

        return $query->whereHas('reservation', fn ($q) => $q->visibleTo($user));
    }

    protected $fillable = [
        'reservation_id',
        'room_id',
        'user_id',
        'order_type',
        'items',
        'total_amount',
        'status',
        'handled_by',
        'special_instructions',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'total_amount' => 'decimal:2',
            'delivered_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /** The step staff move an order to next, or null when it is finished. */
    public const NEXT_STATUS = [
        'pending' => 'preparing',
        'preparing' => 'on_the_way',
        'on_the_way' => 'delivered',
    ];

    public const OPEN_STATUSES = ['pending', 'preparing', 'on_the_way'];

    public function nextStatus(): ?string
    {
        return self::NEXT_STATUS[$this->status] ?? null;
    }

    /**
     * Move the order one step forward (pending -> preparing -> on the way -> delivered),
     * recording who handled it and, optionally, the amount to charge the guest.
     */
    public function advance(User $actor, ?float $charge = null): bool
    {
        $next = $this->nextStatus();
        if (! $next) {
            return false;
        }

        $changes = ['status' => $next, 'handled_by' => $actor->id];
        if ($charge !== null) {
            $changes['total_amount'] = max(0, $charge);
        }
        if ($next === 'delivered') {
            $changes['delivered_at'] = now();
        }

        return $this->update($changes);
    }

    public static function nextActionLabel(string $status): ?string
    {
        return [
            'pending' => 'Accept & start preparing',
            'preparing' => 'Mark on the way',
            'on_the_way' => 'Mark delivered',
        ][$status] ?? null;
    }

    public static function orderTypes()
    {
        return [
            'food' => 'Food & Beverages',
            'laundry' => 'Laundry Service',
            'amenity' => 'Amenity Request',
            'maintenance' => 'Maintenance Request',
            'other' => 'Other',
        ];
    }

    public static function statuses()
    {
        return [
            'pending' => 'Pending',
            'preparing' => 'Preparing',
            'on_the_way' => 'On the Way',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
        ];
    }
}
