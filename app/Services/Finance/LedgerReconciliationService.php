<?php

namespace App\Services\Finance;

use App\Models\Expense;
use App\Models\FinanceEntry;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class LedgerReconciliationService
{
    public function __construct(
        private LedgerPostingService $postingService,
    ) {}

    public function backfill(array $options = []): array
    {
        $chunkSize = $options['chunk'] ?? 100;
        $siteId = $options['site_id'] ?? null;
        $unitId = $options['unit_id'] ?? null;

        $paymentsCount = 0;
        $staysCount = 0;
        $cautionsCount = 0;
        $expensesCount = 0;
        $unresolved = [];

        // 1. Backfill successful payments
        $paymentsQuery = Payment::query()
            ->where('type', 'payment')
            ->where('paystack_status', 'success')
            ->whereHas('reservation', function ($q) use ($siteId, $unitId) {
                if ($unitId) {
                    $q->where('property_id', $unitId);
                } elseif ($siteId) {
                    $q->whereHas('unit', fn ($u) => $u->where('site_id', $siteId));
                }
            })
            ->with(['reservation.unit.owner']);

        $paymentsQuery->chunkById($chunkSize, function ($payments) use (&$paymentsCount, &$unresolved) {
            foreach ($payments as $payment) {
                $reservation = $payment->reservation;
                $unit = $reservation?->unit;

                if (! $unit) {
                    $unresolved[] = [
                        'source_type' => 'payment',
                        'source_id' => $payment->id,
                        'reason' => 'Payment reservation is missing a valid unit.',
                    ];

                    continue;
                }

                if (! $unit->owner?->is_company_owned) {
                    $agreement = $this->postingService->resolveAgreementOptional($unit, $reservation->check_in ?? now());
                    if (! $agreement) {
                        $unresolved[] = [
                            'source_type' => 'payment',
                            'source_id' => $payment->id,
                            'property_id' => $unit->id,
                            'unit_code' => $unit->unit_code,
                            'date' => $reservation->check_in?->toDateString(),
                            'reason' => "No reviewed management agreement covers unit {$unit->unit_code} on {$reservation->check_in?->toDateString()}.",
                        ];
                    }
                }

                $this->postingService->postCollection($payment);
                $paymentsCount++;
            }
        });

        // 2. Backfill completed stays
        $staysQuery = Reservation::query()
            ->where('booking_status', 'checked_out')
            ->where('payment_status', 'paid')
            ->when($unitId, fn ($q) => $q->where('property_id', $unitId))
            ->when($siteId, fn ($q) => $q->whereHas('unit', fn ($u) => $u->where('site_id', $siteId)))
            ->with(['unit.owner']);

        $staysQuery->chunkById($chunkSize, function ($stays) use (&$staysCount, &$unresolved) {
            foreach ($stays as $stay) {
                $unit = $stay->unit;

                if (! $unit) {
                    $unresolved[] = [
                        'source_type' => 'reservation',
                        'source_id' => $stay->id,
                        'reason' => 'Completed stay is missing a valid unit.',
                    ];

                    continue;
                }

                if (! $unit->owner?->is_company_owned) {
                    $agreement = $this->postingService->resolveAgreementOptional($unit, $stay->check_in ?? now());
                    if (! $agreement) {
                        $unresolved[] = [
                            'source_type' => 'reservation',
                            'source_id' => $stay->id,
                            'property_id' => $unit->id,
                            'unit_code' => $unit->unit_code,
                            'date' => $stay->check_in?->toDateString(),
                            'reason' => "No reviewed management agreement covers stay {$stay->booking_ref} on {$stay->check_in?->toDateString()}.",
                        ];
                    }
                }

                $this->postingService->postStayCompletion($stay);
                $staysCount++;
            }
        });

        // 3. Backfill caution resolutions
        $cautionsQuery = Reservation::query()
            ->whereIn('caution_fee_status', ['refunded', 'forfeited'])
            ->when($unitId, fn ($q) => $q->where('property_id', $unitId))
            ->when($siteId, fn ($q) => $q->whereHas('unit', fn ($u) => $u->where('site_id', $siteId)));

        $cautionsQuery->chunkById($chunkSize, function ($reservations) use (&$cautionsCount) {
            foreach ($reservations as $reservation) {
                if ($reservation->caution_fee_status === 'refunded') {
                    $this->postingService->postCautionRefund($reservation);
                } elseif ($reservation->caution_fee_status === 'forfeited') {
                    $this->postingService->postCautionForfeiture($reservation);
                }
                $cautionsCount++;
            }
        });

        // 4. Backfill property expenses
        $expensesQuery = Expense::query()
            ->when($unitId, fn ($q) => $q->where('property_id', $unitId))
            ->when($siteId, fn ($q) => $q->whereHas('property', fn ($p) => $p->where('site_id', $siteId)))
            ->with(['property.owner']);

        $expensesQuery->chunkById($chunkSize, function ($expenses) use (&$expensesCount, &$unresolved) {
            foreach ($expenses as $expense) {
                $unit = $expense->property;
                if (! $unit) {
                    $unresolved[] = [
                        'source_type' => 'expense',
                        'source_id' => $expense->id,
                        'reason' => 'Expense is missing a valid property.',
                    ];

                    continue;
                }

                $this->postingService->postExpense($expense);
                $expensesCount++;
            }
        });

        return [
            'payments_processed' => $paymentsCount,
            'stays_processed' => $staysCount,
            'cautions_processed' => $cautionsCount,
            'expenses_processed' => $expensesCount,
            'unresolved_count' => count($unresolved),
            'unresolved' => $unresolved,
        ];
    }

    public function reconcile(array $options = []): array
    {
        $siteId = $options['site_id'] ?? null;
        $unitId = $options['unit_id'] ?? null;
        $periodStart = $options['period_start'] ?? null;
        $periodEnd = $options['period_end'] ?? null;

        $differences = [];
        $unresolved = [];

        // Invariant 1: Every journal transaction must balance to zero
        $imbalancedTx = DB::table('finance_entries')
            ->select('finance_transaction_id', DB::raw('SUM(amount_minor) as balance'))
            ->groupBy('finance_transaction_id')
            ->having('balance', '!=', 0)
            ->get();

        foreach ($imbalancedTx as $row) {
            $differences[] = [
                'type' => 'imbalance',
                'transaction_id' => $row->finance_transaction_id,
                'difference_minor' => $row->balance,
                'message' => "Transaction #{$row->finance_transaction_id} is imbalanced by {$row->balance} kobo.",
            ];
        }

        // Invariant 2: External-owned unit entries must have an agreement attached
        $unattributedEntries = FinanceEntry::query()
            ->whereNull('management_agreement_id')
            ->whereHas('owner', fn ($q) => $q->where('is_company_owned', false))
            ->when($unitId, fn ($q) => $q->where('unit_id', $unitId))
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->when($periodStart, fn ($q) => $q->whereDate('occurred_on', '>=', $periodStart))
            ->when($periodEnd, fn ($q) => $q->whereDate('occurred_on', '<=', $periodEnd))
            ->with(['unit', 'owner'])
            ->get();

        foreach ($unattributedEntries as $entry) {
            $unresolved[] = [
                'type' => 'missing_agreement',
                'entry_id' => $entry->id,
                'unit_id' => $entry->unit_id,
                'unit_code' => $entry->unit->unit_code ?? null,
                'owner_id' => $entry->owner_id,
                'occurred_on' => $entry->occurred_on->toDateString(),
                'message' => "Entry #{$entry->id} for external unit {$entry->unit?->unit_code} has no management agreement attached.",
            ];
        }

        // Invariant 3: Source Collections vs Journal Collections
        $unitsQuery = Unit::query()
            ->when($unitId, fn ($q) => $q->where('id', $unitId))
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId));

        $units = $unitsQuery->get();
        $byProperty = [];

        $totalSourceCollections = 0;
        $totalJournalCollections = 0;
        $totalSourceExpenses = 0;
        $totalJournalExpenses = 0;

        foreach ($units as $unit) {
            // Source payments
            $sourcePayments = (float) Payment::where('type', 'payment')
                ->where('paystack_status', 'success')
                ->whereHas('reservation', fn ($q) => $q->where('property_id', $unit->id)
                    ->when($periodStart, fn ($p) => $p->whereDate('check_in', '>=', $periodStart))
                    ->when($periodEnd, fn ($p) => $p->whereDate('check_in', '<=', $periodEnd)))
                ->sum('amount');
            $sourcePaymentsMinor = (int) round($sourcePayments * 100);

            // Journal collections (cash + provider_clearing from collection events)
            $journalCollectionsMinor = (int) FinanceEntry::where('unit_id', $unit->id)
                ->whereIn('account', [FinanceEntry::ACCOUNT_CASH, FinanceEntry::ACCOUNT_PROVIDER_CLEARING])
                ->whereHas('transaction', fn ($q) => $q->where('event_type', 'collection'))
                ->when($periodStart, fn ($p) => $p->whereDate('occurred_on', '>=', $periodStart))
                ->when($periodEnd, fn ($p) => $p->whereDate('occurred_on', '<=', $periodEnd))
                ->sum('amount_minor');

            // Source expenses
            $sourceExp = (float) Expense::where('property_id', $unit->id)
                ->when($periodStart, fn ($p) => $p->whereDate('spent_on', '>=', $periodStart))
                ->when($periodEnd, fn ($p) => $p->whereDate('spent_on', '<=', $periodEnd))
                ->sum('amount');
            $sourceExpMinor = (int) round($sourceExp * 100);

            // Journal expenses (owner_recoverable_expense + company_expense)
            $journalExpMinor = (int) FinanceEntry::where('unit_id', $unit->id)
                ->whereIn('account', [FinanceEntry::ACCOUNT_OWNER_RECOVERABLE_EXPENSE, FinanceEntry::ACCOUNT_COMPANY_EXPENSE])
                ->when($periodStart, fn ($p) => $p->whereDate('occurred_on', '>=', $periodStart))
                ->when($periodEnd, fn ($p) => $p->whereDate('occurred_on', '<=', $periodEnd))
                ->sum('amount_minor');

            $colDiff = $sourcePaymentsMinor - $journalCollectionsMinor;
            $expDiff = $sourceExpMinor - $journalExpMinor;

            if ($colDiff !== 0) {
                $differences[] = [
                    'type' => 'collection_mismatch',
                    'unit_id' => $unit->id,
                    'unit_code' => $unit->unit_code,
                    'source_minor' => $sourcePaymentsMinor,
                    'journal_minor' => $journalCollectionsMinor,
                    'difference_minor' => $colDiff,
                    'message' => "Unit {$unit->unit_code} has collection discrepancy of {$colDiff} kobo.",
                ];
            }

            if ($expDiff !== 0) {
                $differences[] = [
                    'type' => 'expense_mismatch',
                    'unit_id' => $unit->id,
                    'unit_code' => $unit->unit_code,
                    'source_minor' => $sourceExpMinor,
                    'journal_minor' => $journalExpMinor,
                    'difference_minor' => $expDiff,
                    'message' => "Unit {$unit->unit_code} has expense discrepancy of {$expDiff} kobo.",
                ];
            }

            $byProperty[$unit->id] = [
                'unit_id' => $unit->id,
                'unit_code' => $unit->unit_code,
                'source_collections_minor' => $sourcePaymentsMinor,
                'journal_collections_minor' => $journalCollectionsMinor,
                'source_expenses_minor' => $sourceExpMinor,
                'journal_expenses_minor' => $journalExpMinor,
            ];

            $totalSourceCollections += $sourcePaymentsMinor;
            $totalJournalCollections += $journalCollectionsMinor;
            $totalSourceExpenses += $sourceExpMinor;
            $totalJournalExpenses += $journalExpMinor;
        }

        $passed = empty($differences) && empty($unresolved);

        return [
            'passed' => $passed,
            'differences' => $differences,
            'unresolved' => $unresolved,
            'totals' => [
                'source_collections_minor' => $totalSourceCollections,
                'journal_collections_minor' => $totalJournalCollections,
                'source_expenses_minor' => $totalSourceExpenses,
                'journal_expenses_minor' => $totalJournalExpenses,
            ],
            'by_property' => $byProperty,
        ];
    }
}
