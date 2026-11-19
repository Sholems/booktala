<?php

namespace App\Services\Finance;

use App\Models\FinanceEntry;
use App\Models\FinanceTransaction;
use App\Models\User;
use App\Support\PropertyAccess;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class GeneralLedgerExportService
{
    /**
     * Standard statutory General Ledger account chart with standard 4-digit codes.
     */
    public const ACCOUNT_CHART = [
        FinanceEntry::ACCOUNT_CASH => [
            'code' => '1010',
            'name' => 'Operating Bank Account (Cash)',
            'type' => 'Asset',
        ],
        FinanceEntry::ACCOUNT_GUEST_RECEIVABLE => [
            'code' => '1020',
            'name' => 'Guest Receivable',
            'type' => 'Asset',
        ],
        FinanceEntry::ACCOUNT_PROVIDER_CLEARING => [
            'code' => '1030',
            'name' => 'Payment Gateway Clearing (Paystack)',
            'type' => 'Asset',
        ],
        FinanceEntry::ACCOUNT_CAUTION_LIABILITY => [
            'code' => '2010',
            'name' => 'Guest Caution Deposit Liability',
            'type' => 'Liability',
        ],
        FinanceEntry::ACCOUNT_DEFERRED_ACCOMMODATION => [
            'code' => '2020',
            'name' => 'Unearned / Deferred Accommodation Revenue',
            'type' => 'Liability',
        ],
        FinanceEntry::ACCOUNT_OWNER_PAYABLE => [
            'code' => '2030',
            'name' => 'Owner / Landlord Payable',
            'type' => 'Liability',
        ],
        FinanceEntry::ACCOUNT_VAT_PAYABLE => [
            'code' => '2040',
            'name' => 'Value Added Tax (VAT) Payable',
            'type' => 'Liability',
        ],
        FinanceEntry::ACCOUNT_PAYOUT_CLEARING => [
            'code' => '2050',
            'name' => 'Owner Payout Clearing',
            'type' => 'Liability',
        ],
        FinanceEntry::ACCOUNT_REFUND_CLEARING => [
            'code' => '2060',
            'name' => 'Guest Refund Clearing',
            'type' => 'Liability',
        ],
        FinanceEntry::ACCOUNT_ACCOMMODATION_REVENUE => [
            'code' => '4010',
            'name' => 'Accommodation Revenue',
            'type' => 'Revenue',
        ],
        FinanceEntry::ACCOUNT_COMMISSION_INCOME => [
            'code' => '4020',
            'name' => 'Management Commission Income',
            'type' => 'Revenue',
        ],
        FinanceEntry::ACCOUNT_PROCESSING_FEE_INCOME => [
            'code' => '4030',
            'name' => 'Processing Fee Income',
            'type' => 'Revenue',
        ],
        FinanceEntry::ACCOUNT_COMPANY_EXPENSE => [
            'code' => '5010',
            'name' => 'Company Operational Expenses',
            'type' => 'Expense',
        ],
        FinanceEntry::ACCOUNT_OWNER_RECOVERABLE_EXPENSE => [
            'code' => '5020',
            'name' => 'Owner Recoverable Expenses',
            'type' => 'Expense',
        ],
    ];

    /**
     * Look up metadata for an account constant.
     */
    public static function accountMeta(string $account): array
    {
        return self::ACCOUNT_CHART[$account] ?? [
            'code' => '9999',
            'name' => ucwords(str_replace('_', ' ', $account)),
            'type' => 'Other',
        ];
    }

    /**
     * Query transactions with entries and metadata for web exploration.
     */
    public function queryLedger(array $filters, ?User $user = null, int $perPage = 25): LengthAwarePaginator
    {
        $query = $this->buildTransactionQuery($filters, $user);

        return $query->with([
            'entries.site',
            'entries.unit',
            'entries.owner',
            'entries.reservation',
            'creator',
        ])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Compute summary aggregates across all entries matching the given filters.
     */
    public function calculateSummary(array $filters, ?User $user = null): array
    {
        $entryQuery = $this->buildEntryQuery($filters, $user);

        $entries = $entryQuery->get(['amount_minor', 'account']);

        $totalDebitsMinor = 0;
        $totalCreditsMinor = 0;

        foreach ($entries as $entry) {
            if ($entry->amount_minor > 0) {
                $totalDebitsMinor += $entry->amount_minor;
            } else {
                $totalCreditsMinor += abs($entry->amount_minor);
            }
        }

        $transactionCount = $this->buildTransactionQuery($filters, $user)->count();

        return [
            'total_debits' => $totalDebitsMinor / 100,
            'total_credits' => $totalCreditsMinor / 100,
            'net_balance' => ($totalDebitsMinor - $totalCreditsMinor) / 100,
            'is_balanced' => $totalDebitsMinor === $totalCreditsMinor,
            'transaction_count' => $transactionCount,
            'entry_count' => $entries->count(),
        ];
    }

    /**
     * Export general ledger as Standard CSV.
     */
    public function exportStandardCsv(array $filters, ?User $user = null): string
    {
        $transactions = $this->buildTransactionQuery($filters, $user)
            ->with([
                'entries.site',
                'entries.unit',
                'entries.owner',
                'entries.reservation',
                'creator',
            ])
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        $rows = [];
        $rows[] = [
            'Transaction Date',
            'Reference',
            'Event Type',
            'Account Code',
            'Account Name',
            'Account Type',
            'Debit (NGN)',
            'Credit (NGN)',
            'Net Amount (NGN)',
            'Site',
            'Property / Unit',
            'Owner / Landlord',
            'Reservation Reference',
            'Description',
            'Source Type',
            'Source ID',
            'Idempotency Key',
        ];

        foreach ($transactions as $tx) {
            foreach ($tx->entries as $entry) {
                $meta = self::accountMeta($entry->account);
                $debit = $entry->amount_minor > 0 ? number_format($entry->amount_minor / 100, 2, '.', '') : '0.00';
                $credit = $entry->amount_minor < 0 ? number_format(abs($entry->amount_minor) / 100, 2, '.', '') : '0.00';
                $net = number_format($entry->amount_minor / 100, 2, '.', '');

                $rows[] = [
                    $tx->occurred_at?->format('Y-m-d H:i:s') ?? $entry->occurred_on?->format('Y-m-d'),
                    $tx->reference,
                    $tx->event_type,
                    $meta['code'],
                    $meta['name'],
                    $meta['type'],
                    $debit,
                    $credit,
                    $net,
                    $entry->site?->name ?? 'Head Office / Unallocated',
                    $entry->unit?->title ?? 'N/A',
                    $entry->owner?->name ?? 'N/A',
                    $entry->reservation?->booking_ref ?? 'N/A',
                    $tx->description ?? '',
                    $tx->source_type ?? '',
                    $tx->source_id ?? '',
                    $tx->idempotency_key,
                ];
            }
        }

        return $this->formatCsv($rows);
    }

    /**
     * Export in QuickBooks Online / Desktop Journal CSV format.
     */
    public function exportQuickBooks(array $filters, ?User $user = null): string
    {
        $transactions = $this->buildTransactionQuery($filters, $user)
            ->with([
                'entries.site',
                'entries.unit',
                'entries.owner',
                'entries.reservation',
            ])
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        $rows = [];
        $rows[] = [
            '*JournalNo',
            '*JournalDate',
            '*Account',
            '*Debit',
            '*Credit',
            'Description',
            'Name',
            'Class',
            'Location',
        ];

        foreach ($transactions as $tx) {
            $journalDate = $tx->occurred_at?->format('m/d/Y') ?? now()->format('m/d/Y');

            foreach ($tx->entries as $entry) {
                $meta = self::accountMeta($entry->account);
                $accountLabel = $meta['code'].' - '.$meta['name'];
                $debit = $entry->amount_minor > 0 ? number_format($entry->amount_minor / 100, 2, '.', '') : '';
                $credit = $entry->amount_minor < 0 ? number_format(abs($entry->amount_minor) / 100, 2, '.', '') : '';
                $name = $entry->owner?->name ?? ($entry->reservation?->guest_name ?? '');
                $class = $entry->site?->name ?? 'Corporate';
                $location = $entry->unit?->title ?? '';

                $rows[] = [
                    $tx->reference,
                    $journalDate,
                    $accountLabel,
                    $debit,
                    $credit,
                    $tx->description ?? '',
                    $name,
                    $class,
                    $location,
                ];
            }
        }

        return $this->formatCsv($rows);
    }

    /**
     * Export in Xero Manual Journal Entry CSV format.
     */
    public function exportXero(array $filters, ?User $user = null): string
    {
        $transactions = $this->buildTransactionQuery($filters, $user)
            ->with([
                'entries.site',
                'entries.unit',
                'entries.owner',
            ])
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        $rows = [];
        $rows[] = [
            '*Narration',
            '*Date',
            '*Description',
            '*AccountCode',
            '*TaxType',
            '*LineAmount',
            'TrackingName1',
            'TrackingOption1',
            'TrackingName2',
            'TrackingOption2',
        ];

        foreach ($transactions as $tx) {
            $date = $tx->occurred_at?->format('Y-m-d') ?? now()->format('Y-m-d');
            $narration = $tx->reference.' - '.($tx->description ?? $tx->event_type);

            foreach ($tx->entries as $entry) {
                $meta = self::accountMeta($entry->account);
                $taxType = $entry->account === FinanceEntry::ACCOUNT_VAT_PAYABLE ? 'OUTPUT' : 'NONE';
                // In Xero manual journals: debits positive, credits negative
                $lineAmount = number_format($entry->amount_minor / 100, 2, '.', '');

                $rows[] = [
                    $narration,
                    $date,
                    $tx->description ?? $meta['name'],
                    $meta['code'],
                    $taxType,
                    $lineAmount,
                    'Site',
                    $entry->site?->name ?? 'General',
                    'Property',
                    $entry->unit?->title ?? 'General',
                ];
            }
        }

        return $this->formatCsv($rows);
    }

    /**
     * Build base query for FinanceTransaction honoring filters and staff site access.
     */
    public function buildTransactionQuery(array $filters, ?User $user = null): Builder
    {
        $query = FinanceTransaction::query();

        // Date range filtering
        if (! empty($filters['start_date'])) {
            $start = CarbonImmutable::parse($filters['start_date'])->startOfDay();
            $query->where('occurred_at', '>=', $start);
        }

        if (! empty($filters['end_date'])) {
            $end = CarbonImmutable::parse($filters['end_date'])->endOfDay();
            $query->where('occurred_at', '<=', $end);
        }

        if (! empty($filters['event_type'])) {
            $query->where('event_type', $filters['event_type']);
        }

        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', $search)
                    ->orWhere('description', 'like', $search)
                    ->orWhere('idempotency_key', 'like', $search);
            });
        }

        // Subquery on entries for dimensional filters
        $needsEntryFilter = ! empty($filters['site_id'])
            || ! empty($filters['unit_id'])
            || ! empty($filters['owner_id'])
            || ! empty($filters['account'])
            || ($user !== null && ! PropertyAccess::seesAll($user));

        if ($needsEntryFilter) {
            $query->whereHas('entries', function (Builder $eq) use ($filters, $user) {
                $this->applyEntryConstraints($eq, $filters, $user);
            });
        }

        return $query;
    }

    /**
     * Build base query for FinanceEntry directly.
     */
    public function buildEntryQuery(array $filters, ?User $user = null): Builder
    {
        $query = FinanceEntry::query();

        // Date filter
        if (! empty($filters['start_date'])) {
            $start = CarbonImmutable::parse($filters['start_date'])->startOfDay();
            $query->where('occurred_on', '>=', $start->toDateString());
        }

        if (! empty($filters['end_date'])) {
            $end = CarbonImmutable::parse($filters['end_date'])->endOfDay();
            $query->where('occurred_on', '<=', $end->toDateString());
        }

        $this->applyEntryConstraints($query, $filters, $user);

        if (! empty($filters['event_type']) || ! empty($filters['search'])) {
            $query->whereHas('transaction', function (Builder $tq) use ($filters) {
                if (! empty($filters['event_type'])) {
                    $tq->where('event_type', $filters['event_type']);
                }
                if (! empty($filters['search'])) {
                    $search = '%'.$filters['search'].'%';
                    $tq->where(function ($q) use ($search) {
                        $q->where('reference', 'like', $search)
                            ->orWhere('description', 'like', $search)
                            ->orWhere('idempotency_key', 'like', $search);
                    });
                }
            });
        }

        return $query;
    }

    /**
     * Apply common dimensional constraints on entry builder.
     */
    protected function applyEntryConstraints(Builder $query, array $filters, ?User $user = null): void
    {
        if (! empty($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }

        if (! empty($filters['unit_id'])) {
            $query->where('unit_id', $filters['unit_id']);
        }

        if (! empty($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }

        if (! empty($filters['account'])) {
            $query->where('account', $filters['account']);
        }

        // Restrict by PropertyAccess if user does not see all
        if ($user !== null && ! PropertyAccess::seesAll($user)) {
            $query->whereIn('unit_id', PropertyAccess::visiblePropertyIds($user));
        }
    }

    /**
     * Convert an array of row arrays to RFC 4180 CSV string.
     */
    protected function formatCsv(array $rows): string
    {
        $stream = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return $csv;
    }
}
