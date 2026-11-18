<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Who a property belongs to: G-Briel itself (is_company_owned) or a landlord
 * whose property G-Briel manages.
 */
class Owner extends Model
{
    protected $fillable = [
        'name', 'type', 'is_company_owned', 'email', 'phone', 'address', 'user_id', 'notes', 'is_active',
        'commission_rate', 'deduct_expenses', 'bank_name', 'bank_account_name', 'bank_account_number',
    ];

    protected function casts(): array
    {
        return [
            'is_company_owned' => 'boolean',
            'is_active' => 'boolean',
            'deduct_expenses' => 'boolean',
            'commission_rate' => 'decimal:2',
        ];
    }

    public function statements(): HasMany
    {
        return $this->hasMany(OwnerStatement::class)->orderByDesc('period_start');
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function managementAgreements(): HasMany
    {
        return $this->hasMany(ManagementAgreement::class);
    }

    public function financeEntries(): HasMany
    {
        return $this->hasMany(FinanceEntry::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * G-Briel's own portfolio; new properties default to it.
     */
    public static function company(): self
    {
        return static::where('is_company_owned', true)->orderBy('id')->first()
            ?? static::create(['name' => 'G-Briel Property Ltd', 'type' => 'company', 'is_company_owned' => true]);
    }
}
