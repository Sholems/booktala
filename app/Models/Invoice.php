<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'reservation_id',
        'user_id',
        'guest_id',
        'subtotal',
        'vat_amount',
        'processing_fee',
        'caution_fee',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'balance',
        'status',
        'due_date',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'processing_fee' => 'decimal:2',
            'caution_fee' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'datetime',
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
        static::creating(function (Invoice $invoice) {
            $invoice->guest_id ??= $invoice->reservation?->guest_id;
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public static function statuses()
    {
        return [
            'draft' => 'Draft',
            'issued' => 'Issued',
            'partial' => 'Partially Paid',
            'paid' => 'Paid',
            'overdue' => 'Overdue',
            'cancelled' => 'Cancelled',
        ];
    }

    public static function generateInvoiceNumber()
    {
        return 'INV-'.date('Y').'-'.str_pad(Invoice::count() + 1, 5, '0', STR_PAD_LEFT);
    }
}
