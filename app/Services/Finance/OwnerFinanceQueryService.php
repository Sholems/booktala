<?php

namespace App\Services\Finance;

use App\Models\FinanceEntry;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\Reservation;
use App\Models\Unit;
use Carbon\CarbonImmutable;

class OwnerFinanceQueryService
{
    /**
     * Query operational finance data for an owner grouped by property/unit.
     *
     * @param  string|null  $period  Month in 'YYYY-MM' format or null for current month
     * @param  int|null  $propertyId  Optional single property filter
     */
    public function queryDashboard(Owner $owner, ?string $period = null, ?int $propertyId = null): array
    {
        $currentMonth = $period ? CarbonImmutable::parse($period.'-01') : CarbonImmutable::now()->startOfMonth();
        $start = $currentMonth->startOfMonth();
        $end = $start->endOfMonth();

        // Retrieve properties/units strictly belonging to this owner (AE5)
        $unitsQuery = $owner->units()->with('site')->orderBy('title');

        if ($propertyId !== null) {
            $unitsQuery->where('id', $propertyId);
        }

        $units = $unitsQuery->get();
        $unitIds = $units->pluck('id')->all();

        // If a specific property was requested but doesn't belong to this owner, return empty structure
        if ($propertyId !== null && empty($unitIds)) {
            return [
                'period' => $currentMonth->format('Y-m'),
                'period_label' => $currentMonth->format('F Y'),
                'properties' => [],
                'totals' => $this->emptyTotals(),
                'available_properties' => $owner->units()->orderBy('title')->get(['id', 'title']),
            ];
        }

        // Query entries strictly for this owner and the scoped units in this period
        $entries = FinanceEntry::query()
            ->where('owner_id', $owner->id)
            ->whereIn('unit_id', $unitIds)
            ->whereBetween('occurred_on', [$start->toDateString(), $end->toDateString()])
            ->with(['transaction'])
            ->get();

        // Calculate nights booked in this period for each unit
        $stays = Reservation::whereIn('property_id', $unitIds)
            ->whereIn('booking_status', ['confirmed', 'checked_in', 'checked_out'])
            ->whereDate('check_in', '<=', $end)
            ->whereDate('check_out', '>', $start)
            ->get();

        $nightsByUnit = $stays->groupBy('property_id')->map(function ($rows) use ($start, $end) {
            return $rows->sum(function ($r) use ($start, $end) {
                $from = CarbonImmutable::parse($r->check_in)->max($start);
                $to = CarbonImmutable::parse($r->check_out)->min($end->addDay()->startOfDay());

                return max(0, (int) $from->diffInDays($to));
            });
        });

        // Group entries by unit_id
        $entriesByUnit = $entries->groupBy('unit_id');

        $propertyRows = [];
        $totalCollected = 0.0;
        $totalCautionHeld = 0.0;
        $totalEarnedRevenue = 0.0;
        $totalCommission = 0.0;
        $totalExpenses = 0.0;
        $totalNetEntitlement = 0.0;
        $totalApproved = 0.0;
        $totalPaid = 0.0;

        foreach ($units as $unit) {
            $unitEntries = $entriesByUnit->get($unit->id, collect());

            // Collections: accommodation + caution fee collected for this unit in period
            $collectionTxIds = $unitEntries
                ->where('transaction.event_type', 'collection')
                ->pluck('finance_transaction_id')
                ->unique();

            $collectedMinor = 0;
            if ($collectionTxIds->isNotEmpty()) {
                // Sum positive cash/clearing entries attributed to this unit
                $collectedMinor = (int) abs($unitEntries
                    ->whereIn('finance_transaction_id', $collectionTxIds)
                    ->whereIn('account', [
                        FinanceEntry::ACCOUNT_DEFERRED_ACCOMMODATION,
                        FinanceEntry::ACCOUNT_CAUTION_LIABILITY,
                    ])
                    ->where('amount_minor', '<', 0)
                    ->sum('amount_minor'));
            }

            // Caution liability held (credit balance is negative)
            $cautionSumMinor = (int) $unitEntries
                ->where('account', FinanceEntry::ACCOUNT_CAUTION_LIABILITY)
                ->sum('amount_minor');
            $cautionHeldMinor = max(0, -$cautionSumMinor);

            // Earned accommodation revenue recognized on stay completion
            $earnedMinor = (int) $unitEntries
                ->where('account', FinanceEntry::ACCOUNT_DEFERRED_ACCOMMODATION)
                ->where('transaction.event_type', 'stay_completion')
                ->where('amount_minor', '>', 0)
                ->sum('amount_minor');

            // Commission deductions
            $commissionMinor = (int) abs($unitEntries
                ->where('account', FinanceEntry::ACCOUNT_COMMISSION_INCOME)
                ->where('amount_minor', '<', 0)
                ->sum('amount_minor'));

            // Recoverable expenses
            $expensesMinor = (int) $unitEntries
                ->where('account', FinanceEntry::ACCOUNT_OWNER_RECOVERABLE_EXPENSE)
                ->where('amount_minor', '>', 0)
                ->sum('amount_minor');

            // Net operational entitlement
            $netEntitlementMinor = $earnedMinor - $commissionMinor - $expensesMinor;

            // Approved & Paid statements for this unit in this month
            // Statement lines link property_id to statements
            $approvedStatements = OwnerStatement::query()
                ->where('owner_id', $owner->id)
                ->whereDate('period_start', $start)
                ->whereIn('status', ['approved', 'paid'])
                ->get();

            $paidStatements = OwnerStatement::query()
                ->where('owner_id', $owner->id)
                ->whereDate('period_start', $start)
                ->where('status', 'paid')
                ->get();

            $unitNights = (int) ($nightsByUnit[$unit->id] ?? 0);

            $collected = round($collectedMinor / 100, 2);
            $cautionHeld = round($cautionHeldMinor / 100, 2);
            $earnedRevenue = round($earnedMinor / 100, 2);
            $commission = round($commissionMinor / 100, 2);
            $expenses = round($expensesMinor / 100, 2);
            $netEntitlement = round($netEntitlementMinor / 100, 2);

            $totalCollected += $collected;
            $totalCautionHeld += $cautionHeld;
            $totalEarnedRevenue += $earnedRevenue;
            $totalCommission += $commission;
            $totalExpenses += $expenses;
            $totalNetEntitlement += $netEntitlement;

            $propertyRows[] = [
                'unit' => $unit,
                'property_id' => $unit->id,
                'title' => $unit->title,
                'unit_code' => $unit->unit_code,
                'site_name' => $unit->site->name ?? $unit->city,
                'nights_booked' => $unitNights,
                'collected' => $collected,
                'caution_held' => $cautionHeld,
                'earned_revenue' => $earnedRevenue,
                'commission_deductions' => $commission,
                'recoverable_expenses' => $expenses,
                'net_entitlement' => $netEntitlement,
            ];
        }

        // Period statements
        $statementForPeriod = OwnerStatement::where('owner_id', $owner->id)
            ->whereDate('period_start', $start)
            ->first();

        $approvedAmount = $statementForPeriod && in_array($statementForPeriod->status, ['approved', 'paid'], true)
            ? (float) $statementForPeriod->net_payout
            : 0.0;

        $paidAmount = $statementForPeriod && $statementForPeriod->status === 'paid'
            ? (float) $statementForPeriod->net_payout
            : 0.0;

        return [
            'period' => $currentMonth->format('Y-m'),
            'period_label' => $currentMonth->format('F Y'),
            'properties' => $propertyRows,
            'totals' => [
                'total_collected' => round($totalCollected, 2),
                'total_caution_held' => round($totalCautionHeld, 2),
                'total_earned_revenue' => round($totalEarnedRevenue, 2),
                'total_commission' => round($totalCommission, 2),
                'total_expenses' => round($totalExpenses, 2),
                'total_net_entitlement' => round($totalNetEntitlement, 2),
                'total_approved' => round($approvedAmount, 2),
                'total_paid' => round($paidAmount, 2),
            ],
            'statement' => $statementForPeriod,
            'available_properties' => $owner->units()->orderBy('title')->get(['id', 'title']),
        ];
    }

    private function emptyTotals(): array
    {
        return [
            'total_collected' => 0.0,
            'total_caution_held' => 0.0,
            'total_earned_revenue' => 0.0,
            'total_commission' => 0.0,
            'total_expenses' => 0.0,
            'total_net_entitlement' => 0.0,
            'total_approved' => 0.0,
            'total_paid' => 0.0,
        ];
    }
}
