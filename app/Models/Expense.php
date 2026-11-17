<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Money spent on a property. "owner" expenses are deducted on the owner's
 * statement (when their agreement allows); "company" expenses are G-Briel's cost.
 */
class Expense extends Model
{
    use \App\Traits\VisibleByProperty;

    public const CATEGORIES = [
        'repairs' => 'Repairs & maintenance',
        'utilities' => 'Electricity, water & gas',
        'diesel' => 'Diesel / generator',
        'internet' => 'Internet & TV',
        'cleaning' => 'Cleaning supplies',
        'laundry' => 'Laundry',
        'security' => 'Security',
        'service_charge' => 'Estate service charge',
        'furnishing' => 'Furnishing & equipment',
        'other' => 'Other',
    ];

    protected $fillable = [
        'property_id', 'category', 'description', 'amount', 'spent_on', 'charge_to',
        'maintenance_report_id', 'receipt_path', 'owner_statement_id', 'recorded_by',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'spent_on' => 'date'];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function maintenanceReport(): BelongsTo
    {
        return $this->belongsTo(MaintenanceReport::class);
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(OwnerStatement::class, 'owner_statement_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** Locked once it appears on an approved or paid statement. */
    public function isLocked(): bool
    {
        return $this->statement !== null && $this->statement->status !== 'draft';
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }
}
