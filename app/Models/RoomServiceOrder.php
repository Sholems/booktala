<?php

namespace App\Models;

use App\Support\PropertyAccess;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomServiceOrder extends Model
{
    use HasFactory;

    public const ORDER_FOOD = 'food';

    public const ORDER_LAUNDRY = 'laundry';

    public const ORDER_HOUSEKEEPING = 'housekeeping';

    public const ORDER_AIRPORT_PICKUP = 'airport_pickup';

    public const ORDER_AMENITY = 'amenity';

    public const ORDER_MAINTENANCE = 'maintenance';

    public const ORDER_OTHER = 'other';

    public const PAYMENT_METHOD_FOLIO = 'folio';

    public const PAYMENT_METHOD_PAYSTACK = 'paystack';

    public const PAYMENT_METHOD_CASH = 'cash';

    public const PAYMENT_STATUS_CHARGED_TO_FOLIO = 'charged_to_folio';

    public const PAYMENT_STATUS_PAID = 'paid';

    public const PAYMENT_STATUS_UNPAID = 'unpaid';

    /**
     * Preset catalog services available to guests in residence.
     */
    public const PRESET_CATALOG = [
        self::ORDER_AIRPORT_PICKUP => [
            [
                'name' => 'Airport Pickup (Murtala Muhammed Int. to Island)',
                'description' => 'Dedicated executive chauffeur pickup from MMIA to VI / Lekki / Ikoyi',
                'price' => 30000.00,
            ],
            [
                'name' => 'Airport Dropoff (Island to MMIA)',
                'description' => 'Transfer from residence to airport with luggage assistance',
                'price' => 25000.00,
            ],
        ],
        self::ORDER_HOUSEKEEPING => [
            [
                'name' => 'Mid-Stay Fresh Linen & Towel Refresh',
                'description' => 'Full replacement of bed sheets, pillowcases and luxury bath towels',
                'price' => 7500.00,
            ],
            [
                'name' => 'Full Suite Deep Housekeeping',
                'description' => 'Comprehensive dusting, bathroom sanitation, floor mopping and bed making',
                'price' => 15000.00,
            ],
        ],
        self::ORDER_LAUNDRY => [
            [
                'name' => 'Wash, Dry & Fold (Per Basket)',
                'description' => 'Same-day laundry service for up to 15 garments',
                'price' => 8000.00,
            ],
            [
                'name' => 'Executive Shirt Pressing (5 Shirts)',
                'description' => 'Steam pressed and hung on hangers',
                'price' => 5000.00,
            ],
        ],
        self::ORDER_FOOD => [
            [
                'name' => 'Continental Breakfast Tray',
                'description' => 'Fresh pastries, eggs to order, sausage, baked beans, juice & coffee',
                'price' => 9500.00,
            ],
            [
                'name' => 'Nigerian Jollof Rice with Grilled Chicken',
                'description' => 'Smoky party jollof with spiced grilled chicken and fried plantains',
                'price' => 8500.00,
            ],
            [
                'name' => 'Late Night Beverage & Snack Pack',
                'description' => 'Assorted soft drinks, spring water, plantain chips and roasted cashews',
                'price' => 6000.00,
            ],
        ],
    ];

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
        'payment_method',
        'payment_status',
        'paystack_reference',
        'status',
        'handled_by',
        'special_instructions',
        'pickup_location',
        'pickup_time',
        'flight_number',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'total_amount' => 'decimal:2',
            'pickup_time' => 'datetime',
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

    public static function orderTypes(): array
    {
        return [
            self::ORDER_FOOD => 'Food & Beverages',
            self::ORDER_LAUNDRY => 'Laundry & Dry Cleaning',
            self::ORDER_HOUSEKEEPING => 'Extra Housekeeping & Linen',
            self::ORDER_AIRPORT_PICKUP => 'Airport Pickup & Chauffeur Transfer',
            self::ORDER_AMENITY => 'Extra Amenities Request',
            self::ORDER_MAINTENANCE => 'Maintenance Support',
            self::ORDER_OTHER => 'Other Concierge Request',
        ];
    }

    public static function statuses(): array
    {
        return [
            'pending' => 'Pending',
            'preparing' => 'Preparing / In Progress',
            'on_the_way' => 'On the Way / Dispatched',
            'delivered' => 'Completed / Delivered',
            'cancelled' => 'Cancelled',
        ];
    }
}
