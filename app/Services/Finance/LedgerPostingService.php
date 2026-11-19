<?php

namespace App\Services\Finance;

use App\Models\Expense;
use App\Models\FinanceEntry;
use App\Models\FinanceTransaction;
use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\Unit;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class LedgerPostingService
{
    public function __construct(
        private JournalService $journal,
        private AgreementResolver $agreementResolver,
    ) {}

    public function postCollection(Payment $payment): ?FinanceTransaction
    {
        $reservation = $payment->reservation;
        if (! $reservation) {
            return null;
        }

        $unit = $reservation->unit ?? Unit::find($reservation->property_id);
        if (! $unit) {
            return null;
        }

        $economicDate = $reservation->check_in
            ? CarbonImmutable::instance($reservation->check_in)
            : CarbonImmutable::parse($payment->collected_at ?? $payment->created_at ?? now());

        $agreement = $this->resolveAgreementOptional($unit, $economicDate);
        $ownerId = $agreement?->owner_id ?? $unit->owner_id ?? Owner::company()->id;

        $amountMinor = (int) round(((float) $payment->amount) * 100);
        if ($amountMinor <= 0) {
            return null;
        }

        // Prior collected amount in minor units
        $priorCollected = (float) Payment::where('reservation_id', $reservation->id)
            ->where('id', '!=', $payment->id)
            ->where('type', 'payment')
            ->where('paystack_status', 'success')
            ->sum('amount');
        $priorMinor = (int) round($priorCollected * 100);
        $totalCollectedMinor = $priorMinor + $amountMinor;

        $billMinor = (int) round(((float) $reservation->total_amount) * 100);
        $procFeeMinor = (int) round(((float) $reservation->processing_fee) * 100);
        $cautionFeeMinor = (int) round(((float) $reservation->caution_fee) * 100);
        $middleTotalMinor = max(0, $billMinor - $procFeeMinor - $cautionFeeMinor);

        // KTD10 allocation:
        // 1. Processing fee
        $deltaProc = max(0, min($procFeeMinor, $totalCollectedMinor) - min($procFeeMinor, $priorMinor));

        // 2. Caution fee (settles last)
        $deltaCaution = max(0, min($cautionFeeMinor, max(0, $totalCollectedMinor - $procFeeMinor - $middleTotalMinor))
            - min($cautionFeeMinor, max(0, $priorMinor - $procFeeMinor - $middleTotalMinor)));

        // 3. Middle portion (accommodation + VAT)
        $deltaMiddle = $amountMinor - $deltaProc - $deltaCaution;

        $vatMinor = (int) round(((float) $reservation->tax_amount) * 100);
        $subtotalAfterDiscount = max(0, (float) $reservation->subtotal - (float) $reservation->discount_amount);
        $accomMinor = (int) round($subtotalAfterDiscount * 100);
        $middleBase = $accomMinor + $vatMinor;

        if ($middleBase > 0) {
            $deltaVat = (int) round($deltaMiddle * ($vatMinor / $middleBase));
        } else {
            $deltaVat = 0;
        }
        $deltaAccom = $deltaMiddle - $deltaVat;

        $assetAccount = ($payment->provider === 'offline' && $payment->payment_method === 'cash')
            ? FinanceEntry::ACCOUNT_CASH
            : FinanceEntry::ACCOUNT_PROVIDER_CLEARING;

        $occurredAt = $payment->collected_at ?? $payment->created_at ?? now();
        $occurredOn = CarbonImmutable::parse($occurredAt)->toDateString();

        $dimensions = [
            'owner_id' => $ownerId,
            'site_id' => $unit->site_id,
            'unit_id' => $unit->id,
            'reservation_id' => $reservation->id,
            'management_agreement_id' => $agreement?->id,
            'occurred_on' => $occurredOn,
        ];

        $entries = [
            [
                'account' => $assetAccount,
                'amount_minor' => $amountMinor,
                ...$dimensions,
            ],
        ];

        if ($deltaProc > 0) {
            $entries[] = [
                'account' => FinanceEntry::ACCOUNT_PROCESSING_FEE_INCOME,
                'amount_minor' => -$deltaProc,
                ...$dimensions,
            ];
        }

        if ($deltaAccom > 0) {
            $entries[] = [
                'account' => FinanceEntry::ACCOUNT_DEFERRED_ACCOMMODATION,
                'amount_minor' => -$deltaAccom,
                ...$dimensions,
            ];
        }

        if ($deltaVat > 0) {
            $entries[] = [
                'account' => FinanceEntry::ACCOUNT_VAT_PAYABLE,
                'amount_minor' => -$deltaVat,
                ...$dimensions,
            ];
        }

        if ($deltaCaution > 0) {
            $entries[] = [
                'account' => FinanceEntry::ACCOUNT_CAUTION_LIABILITY,
                'amount_minor' => -$deltaCaution,
                ...$dimensions,
            ];
        }

        return $this->journal->post([
            'idempotency_key' => "payment:{$payment->id}:collection",
            'source_type' => 'payment',
            'source_id' => (string) $payment->id,
            'event_type' => 'collection',
            'currency' => 'NGN',
            'occurred_at' => CarbonImmutable::parse($occurredAt)->toDateTimeString(),
            'description' => "Booking collection: payment #{$payment->id}",
            'created_by' => $payment->collected_by ?? $payment->user_id,
            'entries' => $entries,
        ]);
    }

    public function postStayCompletion(Reservation $reservation, ?int $userId = null): ?FinanceTransaction
    {
        $unit = $reservation->unit ?? Unit::find($reservation->property_id);
        if (! $unit) {
            return null;
        }

        $economicDate = $reservation->check_in
            ? CarbonImmutable::instance($reservation->check_in)
            : CarbonImmutable::parse($reservation->checked_out_at ?? now());

        $agreement = $this->resolveAgreementOptional($unit, $economicDate);
        $owner = $unit->owner ?? Owner::company();
        $ownerId = $agreement?->owner_id ?? $owner->id;

        $subtotalAfterDiscount = max(0, (float) $reservation->subtotal - (float) $reservation->discount_amount);
        $accomMinor = (int) round($subtotalAfterDiscount * 100);

        if ($accomMinor <= 0) {
            return null;
        }

        $occurredAt = $reservation->checked_out_at
            ?? ($reservation->check_out ? CarbonImmutable::parse($reservation->check_out)->setTime(11, 0) : now());
        $occurredOn = CarbonImmutable::parse($reservation->check_out ?? $occurredAt)->toDateString();

        $dimensions = [
            'owner_id' => $ownerId,
            'site_id' => $unit->site_id,
            'unit_id' => $unit->id,
            'reservation_id' => $reservation->id,
            'management_agreement_id' => $agreement?->id,
            'occurred_on' => $occurredOn,
        ];

        $entries = [
            [
                'account' => FinanceEntry::ACCOUNT_DEFERRED_ACCOMMODATION,
                'amount_minor' => $accomMinor,
                ...$dimensions,
            ],
        ];

        if ($owner->is_company_owned) {
            $entries[] = [
                'account' => FinanceEntry::ACCOUNT_ACCOMMODATION_REVENUE,
                'amount_minor' => -$accomMinor,
                ...$dimensions,
            ];
        } else {
            $rate = (float) ($agreement?->commission_rate ?? $owner->commission_rate ?? 0);
            $commissionMinor = (int) round($accomMinor * ($rate / 100));
            $ownerPayableMinor = $accomMinor - $commissionMinor;

            if ($commissionMinor > 0) {
                $entries[] = [
                    'account' => FinanceEntry::ACCOUNT_COMMISSION_INCOME,
                    'amount_minor' => -$commissionMinor,
                    ...$dimensions,
                ];
            }

            if ($ownerPayableMinor > 0) {
                $entries[] = [
                    'account' => FinanceEntry::ACCOUNT_OWNER_PAYABLE,
                    'amount_minor' => -$ownerPayableMinor,
                    ...$dimensions,
                ];
            }
        }

        return $this->journal->post([
            'idempotency_key' => "reservation:{$reservation->id}:stay_completion",
            'source_type' => 'reservation',
            'source_id' => (string) $reservation->id,
            'event_type' => 'stay_completion',
            'currency' => 'NGN',
            'occurred_at' => CarbonImmutable::parse($occurredAt)->toDateTimeString(),
            'description' => "Stay completed: booking {$reservation->booking_ref}",
            'created_by' => $userId,
            'entries' => $entries,
        ]);
    }

    public function postCautionRefund(Reservation $reservation, ?float $amount = null, ?int $userId = null): ?FinanceTransaction
    {
        $unit = $reservation->unit ?? Unit::find($reservation->property_id);
        if (! $unit) {
            return null;
        }

        $cautionMinor = (int) round(($amount ?? (float) $reservation->caution_fee) * 100);
        if ($cautionMinor <= 0) {
            return null;
        }

        $economicDate = $reservation->check_in
            ? CarbonImmutable::instance($reservation->check_in)
            : CarbonImmutable::parse($reservation->caution_refunded_at ?? now());

        $agreement = $this->resolveAgreementOptional($unit, $economicDate);
        $ownerId = $agreement?->owner_id ?? $unit->owner_id ?? Owner::company()->id;
        $occurredAt = $reservation->caution_refunded_at ?? now();

        $dimensions = [
            'owner_id' => $ownerId,
            'site_id' => $unit->site_id,
            'unit_id' => $unit->id,
            'reservation_id' => $reservation->id,
            'management_agreement_id' => $agreement?->id,
            'occurred_on' => CarbonImmutable::parse($occurredAt)->toDateString(),
        ];

        return $this->journal->post([
            'idempotency_key' => "reservation:{$reservation->id}:caution_refund",
            'source_type' => 'reservation',
            'source_id' => (string) $reservation->id,
            'event_type' => 'caution_refund',
            'currency' => 'NGN',
            'occurred_at' => CarbonImmutable::parse($occurredAt)->toDateTimeString(),
            'description' => "Caution fee refunded: booking {$reservation->booking_ref}",
            'created_by' => $userId,
            'entries' => [
                [
                    'account' => FinanceEntry::ACCOUNT_CAUTION_LIABILITY,
                    'amount_minor' => $cautionMinor,
                    ...$dimensions,
                ],
                [
                    'account' => FinanceEntry::ACCOUNT_PROVIDER_CLEARING,
                    'amount_minor' => -$cautionMinor,
                    ...$dimensions,
                ],
            ],
        ]);
    }

    public function postCautionForfeiture(Reservation $reservation, ?float $amount = null, ?int $userId = null): ?FinanceTransaction
    {
        $unit = $reservation->unit ?? Unit::find($reservation->property_id);
        if (! $unit) {
            return null;
        }

        $cautionMinor = (int) round(($amount ?? (float) $reservation->caution_fee) * 100);
        if ($cautionMinor <= 0) {
            return null;
        }

        $economicDate = $reservation->check_in
            ? CarbonImmutable::instance($reservation->check_in)
            : CarbonImmutable::parse(now());

        $agreement = $this->resolveAgreementOptional($unit, $economicDate);
        $ownerId = $agreement?->owner_id ?? $unit->owner_id ?? Owner::company()->id;
        $recipient = $agreement?->caution_forfeiture_recipient ?? 'company';
        $occurredAt = now();

        $dimensions = [
            'owner_id' => $ownerId,
            'site_id' => $unit->site_id,
            'unit_id' => $unit->id,
            'reservation_id' => $reservation->id,
            'management_agreement_id' => $agreement?->id,
            'occurred_on' => CarbonImmutable::parse($occurredAt)->toDateString(),
        ];

        $creditAccount = $recipient === 'owner'
            ? FinanceEntry::ACCOUNT_OWNER_PAYABLE
            : FinanceEntry::ACCOUNT_COMMISSION_INCOME;

        return $this->journal->post([
            'idempotency_key' => "reservation:{$reservation->id}:caution_forfeiture",
            'source_type' => 'reservation',
            'source_id' => (string) $reservation->id,
            'event_type' => 'caution_forfeiture',
            'currency' => 'NGN',
            'occurred_at' => CarbonImmutable::parse($occurredAt)->toDateTimeString(),
            'description' => "Caution fee forfeited: booking {$reservation->booking_ref}",
            'created_by' => $userId,
            'entries' => [
                [
                    'account' => FinanceEntry::ACCOUNT_CAUTION_LIABILITY,
                    'amount_minor' => $cautionMinor,
                    ...$dimensions,
                ],
                [
                    'account' => $creditAccount,
                    'amount_minor' => -$cautionMinor,
                    ...$dimensions,
                ],
            ],
        ]);
    }

    public function postExpense(Expense $expense): ?FinanceTransaction
    {
        $property = $expense->property;
        if (! $property) {
            return null;
        }

        $unit = $property instanceof Unit ? $property : Unit::find($property->id);
        if (! $unit) {
            return null;
        }

        $economicDate = CarbonImmutable::parse($expense->spent_on);
        $agreement = $this->resolveAgreementOptional($unit, $economicDate);
        $ownerId = $agreement?->owner_id ?? $unit->owner_id ?? Owner::company()->id;

        $amountMinor = (int) round(((float) $expense->amount) * 100);
        if ($amountMinor <= 0) {
            return null;
        }

        $expenseAccount = $expense->charge_to === 'owner'
            ? FinanceEntry::ACCOUNT_OWNER_RECOVERABLE_EXPENSE
            : FinanceEntry::ACCOUNT_COMPANY_EXPENSE;

        $dimensions = [
            'owner_id' => $ownerId,
            'site_id' => $unit->site_id,
            'unit_id' => $unit->id,
            'management_agreement_id' => $agreement?->id,
            'occurred_on' => $economicDate->toDateString(),
        ];

        return $this->journal->post([
            'idempotency_key' => "expense:{$expense->id}",
            'source_type' => 'expense',
            'source_id' => (string) $expense->id,
            'event_type' => 'expense',
            'currency' => 'NGN',
            'occurred_at' => $economicDate->startOfDay()->toDateTimeString(),
            'description' => "Property expense: {$expense->description}",
            'created_by' => $expense->recorded_by,
            'entries' => [
                [
                    'account' => $expenseAccount,
                    'amount_minor' => $amountMinor,
                    ...$dimensions,
                ],
                [
                    'account' => FinanceEntry::ACCOUNT_CASH,
                    'amount_minor' => -$amountMinor,
                    ...$dimensions,
                ],
            ],
        ]);
    }

    public function postPayout(OwnerStatement $statement, ?string $reference = null, ?int $userId = null): ?FinanceTransaction
    {
        $netMinor = (int) round(((float) $statement->net_payout) * 100);
        if ($netMinor <= 0) {
            return null;
        }

        $owner = $statement->owner;
        $primaryUnit = $owner->units()->first() ?? Unit::where('owner_id', $owner->id)->first();
        if (! $primaryUnit) {
            // Find any unit associated with the owner's statement lines
            $firstLinePropertyId = $statement->lines()->whereNotNull('property_id')->value('property_id');
            $primaryUnit = $firstLinePropertyId ? Unit::find($firstLinePropertyId) : Unit::first();
        }

        $siteId = $primaryUnit?->site_id ?? Site::first()->id;
        $unitId = $primaryUnit?->id ?? Unit::first()->id;

        $dimensions = [
            'owner_id' => $owner->id,
            'site_id' => $siteId,
            'unit_id' => $unitId,
            'occurred_on' => today()->toDateString(),
        ];

        return $this->journal->post([
            'idempotency_key' => "owner_statement:{$statement->id}:payout",
            'source_type' => 'owner_statement',
            'source_id' => (string) $statement->id,
            'event_type' => 'payout',
            'currency' => 'NGN',
            'occurred_at' => now()->toDateTimeString(),
            'description' => "Owner payout for {$statement->periodLabel()} statement (Ref: ".($reference ?: 'N/A').')',
            'created_by' => $userId,
            'entries' => [
                [
                    'account' => FinanceEntry::ACCOUNT_OWNER_PAYABLE,
                    'amount_minor' => $netMinor,
                    ...$dimensions,
                ],
                [
                    'account' => FinanceEntry::ACCOUNT_PAYOUT_CLEARING,
                    'amount_minor' => -$netMinor,
                    ...$dimensions,
                ],
            ],
        ]);
    }

    public function resolveAgreementOptional(Unit $unit, CarbonInterface|string $date): ?ManagementAgreement
    {
        try {
            return $this->agreementResolver->resolve($unit, $date);
        } catch (\DomainException $e) {
            return null;
        }
    }
}
