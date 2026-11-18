<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Monthly owner statements.
 *
 *  Revenue: paid stays at the owner's properties that checked out in the month,
 *  at the room price after discounts (VAT is excluded: it is not the owner's).
 *  Commission: the owner's agreed % of that revenue.
 *  Expenses: owner-chargeable expenses up to the month end that are not yet on
 *  a statement (so a late receipt lands on the next one), if their agreement
 *  deducts expenses.
 */
class OwnerStatementService
{
    public function generate(Owner $owner, CarbonImmutable $month): OwnerStatement
    {
        $start = $month->startOfMonth();
        $end = $month->endOfMonth();

        // Match on the date only: stored dates carry a time part on some databases.
        $statement = OwnerStatement::where('owner_id', $owner->id)->whereDate('period_start', $start)->first()
            ?? new OwnerStatement(['owner_id' => $owner->id, 'period_start' => $start->toDateString()]);
        if ($statement->exists && ! $statement->isDraft()) {
            throw ValidationException::withMessages(['month' => $owner->name."'s ".$statement->periodLabel().' statement is already '.$statement->status.'.']);
        }

        return DB::transaction(function () use ($owner, $statement, $start, $end) {
            $statement->fill([
                'period_end' => $end->toDateString(),
                'commission_rate' => $owner->commission_rate,
                'status' => 'draft',
            ])->save();

            // Start again: release expenses and lines from a previous draft.
            Expense::where('owner_statement_id', $statement->id)->update(['owner_statement_id' => null]);
            $statement->lines()->delete();

            $propertyIds = $owner->properties()->pluck('id');

            $stays = Reservation::whereIn('property_id', $propertyIds)
                ->where('booking_status', 'checked_out')
                ->where('payment_status', 'paid')
                ->whereDate('check_out', '>=', $start)
                ->whereDate('check_out', '<=', $end)
                ->with('property')
                ->orderBy('check_out')
                ->get();

            $gross = 0.0;
            $nights = 0;
            foreach ($stays as $stay) {
                $amount = round((float) $stay->subtotal - (float) $stay->discount_amount, 2);
                $gross += $amount;
                $nights += (int) $stay->nights;
                $statement->lines()->create([
                    'property_id' => $stay->property_id,
                    'type' => 'booking',
                    'source_id' => $stay->id,
                    'date' => $stay->check_out,
                    'description' => 'Stay '.$stay->booking_ref.' · '.$stay->check_in->format('j M').'–'.$stay->check_out->format('j M'),
                    'nights' => $stay->nights,
                    'amount' => $amount,
                ]);
            }

            $expensesTotal = 0.0;
            if ($owner->deduct_expenses) {
                $expenses = Expense::whereIn('property_id', $propertyIds)
                    ->where('charge_to', 'owner')
                    ->whereNull('owner_statement_id')
                    ->whereDate('spent_on', '<=', $end)
                    ->orderBy('spent_on')
                    ->get();

                foreach ($expenses as $expense) {
                    $expensesTotal += (float) $expense->amount;
                    $expense->update(['owner_statement_id' => $statement->id]);
                    $statement->lines()->create([
                        'property_id' => $expense->property_id,
                        'type' => 'expense',
                        'source_id' => $expense->id,
                        'date' => $expense->spent_on,
                        'description' => $expense->categoryLabel().': '.$expense->description,
                        'amount' => -1 * (float) $expense->amount,
                    ]);
                }
            }

            $commission = round($gross * (float) $owner->commission_rate / 100, 2);

            $statement->update([
                'gross_revenue' => round($gross, 2),
                'nights_booked' => $nights,
                'commission_amount' => $commission,
                'expenses_total' => round($expensesTotal, 2),
                'net_payout' => round($gross - $commission - $expensesTotal, 2),
            ]);

            return $statement->fresh('lines');
        });
    }

    /**
     * Draft statements for every active owner with properties.
     */
    public function generateForAll(CarbonImmutable $month): int
    {
        $count = 0;
        foreach (Owner::where('is_active', true)->whereHas('properties')->get() as $owner) {
            $existing = OwnerStatement::where('owner_id', $owner->id)->whereDate('period_start', $month->startOfMonth())->first();
            if ($existing && ! $existing->isDraft()) {
                continue;
            }
            $this->generate($owner, $month);
            $count++;
        }

        return $count;
    }

    public function approve(OwnerStatement $statement, User $by): void
    {
        if (! $statement->isDraft()) {
            throw ValidationException::withMessages(['status' => 'Only a draft statement can be approved.']);
        }

        $statement->update(['status' => 'approved', 'approved_by' => $by->id, 'approved_at' => now()]);
    }

    public function markPaid(OwnerStatement $statement, ?string $reference): void
    {
        if ($statement->status !== 'approved') {
            throw ValidationException::withMessages(['status' => 'Approve the statement before recording the payout.']);
        }

        $statement->update(['status' => 'paid', 'paid_at' => now(), 'payment_reference' => $reference]);
    }
}
