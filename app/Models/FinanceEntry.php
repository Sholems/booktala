<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceEntry extends Model
{
    public const ACCOUNT_ACCOMMODATION_REVENUE = 'accommodation_revenue';

    public const ACCOUNT_CASH = 'cash';

    public const ACCOUNT_CAUTION_LIABILITY = 'caution_liability';

    public const ACCOUNT_COMMISSION_INCOME = 'commission_income';

    public const ACCOUNT_COMPANY_EXPENSE = 'company_expense';

    public const ACCOUNT_DEFERRED_ACCOMMODATION = 'deferred_accommodation';

    public const ACCOUNT_GUEST_RECEIVABLE = 'guest_receivable';

    public const ACCOUNT_OWNER_PAYABLE = 'owner_payable';

    public const ACCOUNT_OWNER_RECOVERABLE_EXPENSE = 'owner_recoverable_expense';

    public const ACCOUNT_PAYOUT_CLEARING = 'payout_clearing';

    public const ACCOUNT_PROCESSING_FEE_INCOME = 'processing_fee_income';

    public const ACCOUNT_PROVIDER_CLEARING = 'provider_clearing';

    public const ACCOUNT_REFUND_CLEARING = 'refund_clearing';

    public const ACCOUNT_VAT_PAYABLE = 'vat_payable';

    public const ACCOUNTS = [
        self::ACCOUNT_ACCOMMODATION_REVENUE,
        self::ACCOUNT_CASH,
        self::ACCOUNT_CAUTION_LIABILITY,
        self::ACCOUNT_COMMISSION_INCOME,
        self::ACCOUNT_COMPANY_EXPENSE,
        self::ACCOUNT_DEFERRED_ACCOMMODATION,
        self::ACCOUNT_GUEST_RECEIVABLE,
        self::ACCOUNT_OWNER_PAYABLE,
        self::ACCOUNT_OWNER_RECOVERABLE_EXPENSE,
        self::ACCOUNT_PAYOUT_CLEARING,
        self::ACCOUNT_PROCESSING_FEE_INCOME,
        self::ACCOUNT_PROVIDER_CLEARING,
        self::ACCOUNT_REFUND_CLEARING,
        self::ACCOUNT_VAT_PAYABLE,
    ];

    protected $fillable = [
        'finance_transaction_id',
        'account',
        'amount_minor',
        'owner_id',
        'site_id',
        'unit_id',
        'reservation_id',
        'management_agreement_id',
        'occurred_on',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Posted journal records are immutable.'));
        static::deleting(fn () => throw new \LogicException('Posted journal records are immutable.'));
    }

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'occurred_on' => 'date',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinanceTransaction::class, 'finance_transaction_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function managementAgreement(): BelongsTo
    {
        return $this->belongsTo(ManagementAgreement::class);
    }
}
