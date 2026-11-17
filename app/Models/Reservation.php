<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\LogsActivity;

class Reservation extends Model
{
    use \App\Traits\VisibleByProperty;
    use LogsActivity;
    protected $fillable = [
        'booking_ref',
        'user_id',
        'property_id',
        'check_in',
        'check_out',
        'nights',
        'guests',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'payment_status',
        'booking_status',
        'payment_method',
        'paystack_reference',
        'special_requests',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
        'confirmed_by',
        'confirmed_at',
        'checked_in_at',
        'checked_in_by',
        'checked_out_at',
        'checked_out_by',
        'refund_status',
    ];

    /*
     * Work queues. The admin dashboard counts these and links to the matching
     * filtered reservation list (?queue=...), so both always agree.
     */
    public const QUEUES = [
        'to_confirm' => 'Bookings to confirm',
        'arrivals' => 'Arriving today',
        'departures' => 'Departures due',
        'unpaid' => 'Confirmed but unpaid',
        'refunds' => 'Refunds to review',
    ];

    public function scopeInQueue($query, string $queue)
    {
        return match ($queue) {
            'to_confirm' => $query->where('booking_status', 'pending')->whereDate('check_out', '>', today()),
            'arrivals' => $query->where('booking_status', 'confirmed')->whereDate('check_in', today()),
            'departures' => $query->where('booking_status', 'checked_in')->whereDate('check_out', '<=', today()),
            'unpaid' => $query->where('booking_status', 'confirmed')->where('payment_status', 'pending')->whereDate('check_out', '>', today()),
            'refunds' => $query->where('refund_status', 'pending'),
            default => $query,
        };
    }

    /**
     * Read-only aliases used by older staff and customer views.
     */
    public function getStatusAttribute(): ?string
    {
        return $this->booking_status;
    }

    public function getConfirmationCodeAttribute(): ?string
    {
        return $this->booking_ref;
    }

    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'cancelled_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public static function generateBookingRef(): string
    {
        return 'WPA-' . strtoupper(uniqid()) . '-' . rand(100, 999);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function guestCheckin(): HasOne
    {
        return $this->hasOne(GuestCheckin::class);
    }

    public function review(): HasOne
    {
        // One guest review per stay.
        return $this->hasOne(Review::class);
    }

    public function invoice(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('check_in', '>=', now()->toDateString())
            ->where('booking_status', 'confirmed');
    }

    public function scopeActive($query)
    {
        return $query->where('booking_status', 'checked_in');
    }
}