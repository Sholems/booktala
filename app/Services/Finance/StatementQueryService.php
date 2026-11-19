<?php

namespace App\Services\Finance;

use App\Models\Expense;
use App\Models\FinanceEntry;
use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\Reservation;
use App\Models\Unit;
use Carbon\CarbonImmutable;

class StatementQueryService
{
    public function __construct(
        private AgreementResolver $agreementResolver,
        private LedgerPostingService $postingService,
    ) {}

    public function queryForStatement(Owner $owner, CarbonImmutable $periodStart, CarbonImmutable $periodEnd): array
    {
        $start = $periodStart->startOfMonth();
        $end = $periodEnd->endOfMonth();
        $propertyIds = $owner->units()->pluck('id')->all();
        if (empty($propertyIds)) {
            $propertyIds = $owner->properties()->pluck('id')->all();
        }

        // Ensure eligible stays in period have been posted to the ledger
        $stays = Reservation::whereIn('property_id', $propertyIds)
            ->where('booking_status', 'checked_out')
            ->where('payment_status', 'paid')
            ->whereDate('check_out', '>=', $start)
            ->whereDate('check_out', '<=', $end)
            ->with(['unit.owner'])
            ->get();

        foreach ($stays as $stay) {
            $this->postingService->postStayCompletion($stay);
        }

        // Ensure eligible expenses have been posted to the ledger
        if ($owner->deduct_expenses) {
            $expenses = Expense::whereIn('property_id', $propertyIds)
                ->where('charge_to', 'owner')
                ->whereDate('spent_on', '<=', $end)
                ->whereNull('owner_statement_id')
                ->get();

            foreach ($expenses as $expense) {
                $this->postingService->postExpense($expense);
            }
        }

        // Query the journal entries for this owner in this period
        $stayCompletionEntries = FinanceEntry::query()
            ->where('owner_id', $owner->id)
            ->whereHas('transaction', fn ($q) => $q->where('event_type', 'stay_completion'))
            ->where(function ($q) use ($start, $end, $stays) {
                $q->whereBetween('occurred_on', [$start->toDateString(), $end->toDateString()]);
                if ($stays->isNotEmpty()) {
                    $q->orWhereIn('reservation_id', $stays->pluck('id'));
                }
            })
            ->with(['reservation.unit', 'unit'])
            ->get();

        $staysByReservation = $stayCompletionEntries->groupBy('reservation_id');

        $lines = [];
        $gross = 0.0;
        $totalCommission = 0.0;
        $totalNights = 0;
        $uncoveredStays = [];

        foreach ($staysByReservation as $reservationId => $entries) {
            $reservation = $entries->first()->reservation ?? Reservation::find($reservationId);
            if (! $reservation) {
                continue;
            }

            $unit = $entries->first()->unit ?? ($reservation->unit ?? Unit::find($reservation->property_id));

            $deferredEntry = $entries->firstWhere('account', FinanceEntry::ACCOUNT_DEFERRED_ACCOMMODATION);
            $commissionEntry = $entries->firstWhere('account', FinanceEntry::ACCOUNT_COMMISSION_INCOME);
            $ownerPayableEntry = $entries->firstWhere('account', FinanceEntry::ACCOUNT_OWNER_PAYABLE);

            $stayGross = $deferredEntry
                ? ((float) $deferredEntry->amount_minor) / 100
                : round((float) $reservation->subtotal - (float) $reservation->discount_amount, 2);

            $commission = $commissionEntry
                ? abs((float) $commissionEntry->amount_minor) / 100
                : round($stayGross * (float) ($owner->commission_rate ?? 0) / 100, 2);

            $ownerPayable = $ownerPayableEntry
                ? abs((float) $ownerPayableEntry->amount_minor) / 100
                : ($stayGross - $commission);

            // Check agreement coverage for external owners (AE6)
            if (! $owner->is_company_owned) {
                $agreementId = $entries->whereNotNull('management_agreement_id')->value('management_agreement_id');
                $covered = false;
                if ($agreementId) {
                    $covered = ManagementAgreement::where('id', $agreementId)
                        ->where('status', ManagementAgreement::STATUS_REVIEWED)
                        ->exists();
                }

                if (! $covered && $unit) {
                    try {
                        $this->agreementResolver->resolve($unit, $reservation->check_in);
                        $covered = true;
                    } catch (\DomainException) {
                        $covered = false;
                    }
                }

                if (! $covered && $unit) {
                    $uncoveredStays[] = [
                        'unit' => $unit,
                        'reservation' => $reservation,
                        'check_in' => $reservation->check_in->toDateString(),
                    ];
                }
            }

            $gross += $stayGross;
            $totalCommission += $commission;
            $totalNights += (int) $reservation->nights;

            $lines[] = [
                'property_id' => $reservation->property_id,
                'type' => 'booking',
                'source_id' => $reservation->id,
                'date' => $reservation->check_out,
                'description' => 'Stay '.$reservation->booking_ref.' · '.$reservation->check_in->format('j M').'–'.$reservation->check_out->format('j M'),
                'nights' => $reservation->nights,
                'amount' => $stayGross,
            ];
        }

        // Expenses
        $expensesTotal = 0.0;
        if ($owner->deduct_expenses) {
            $eligibleExpenses = Expense::whereIn('property_id', $propertyIds)
                ->where('charge_to', 'owner')
                ->whereDate('spent_on', '<=', $end)
                ->whereNull('owner_statement_id')
                ->orderBy('spent_on')
                ->get();

            foreach ($eligibleExpenses as $expense) {
                $expensesTotal += (float) $expense->amount;
                $lines[] = [
                    'property_id' => $expense->property_id,
                    'type' => 'expense',
                    'source_id' => $expense->id,
                    'date' => $expense->spent_on,
                    'description' => $expense->categoryLabel().': '.$expense->description,
                    'nights' => null,
                    'amount' => -1 * (float) $expense->amount,
                    'expense_model' => $expense,
                ];
            }
        }

        $gross = round($gross, 2);
        $totalCommission = round($totalCommission, 2);
        $expensesTotal = round($expensesTotal, 2);
        $netPayout = round($gross - $totalCommission - $expensesTotal, 2);

        return [
            'gross_revenue' => $gross,
            'commission_amount' => $totalCommission,
            'expenses_total' => $expensesTotal,
            'net_payout' => $netPayout,
            'nights_booked' => $totalNights,
            'lines' => $lines,
            'uncovered_stays' => $uncoveredStays,
        ];
    }
}
