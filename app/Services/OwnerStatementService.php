<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\Reservation;
use App\Models\Unit;
use App\Models\User;
use App\Services\Finance\LedgerPostingService;
use App\Services\Finance\StatementQueryService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Monthly owner statements backed by the operational subledger.
 */
class OwnerStatementService
{
    public function __construct(
        private StatementQueryService $queryService,
        private LedgerPostingService $postingService,
    ) {}

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

            // Release expenses and lines from a previous draft
            Expense::where('owner_statement_id', $statement->id)->update(['owner_statement_id' => null]);
            $statement->lines()->delete();

            $data = $this->queryService->queryForStatement($owner, $start, $end);

            foreach ($data['lines'] as $lineData) {
                if (isset($lineData['expense_model'])) {
                    $lineData['expense_model']->update(['owner_statement_id' => $statement->id]);
                    unset($lineData['expense_model']);
                }

                $statement->lines()->create($lineData);
            }

            $statement->update([
                'gross_revenue' => $data['gross_revenue'],
                'nights_booked' => $data['nights_booked'],
                'commission_amount' => $data['commission_amount'],
                'expenses_total' => $data['expenses_total'],
                'net_payout' => $data['net_payout'],
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

        $owner = $statement->owner;
        if (! $owner->is_company_owned) {
            // Check agreement coverage for every stay in the statement (AE6)
            foreach ($statement->lines()->where('type', 'booking')->get() as $line) {
                $reservation = Reservation::find($line->source_id);
                if ($reservation) {
                    $unit = $reservation->unit ?? Unit::find($reservation->property_id);
                    $checkInDate = $reservation->check_in->toDateString();
                    $covered = ManagementAgreement::query()
                        ->where('unit_id', $unit->id)
                        ->where('status', ManagementAgreement::STATUS_REVIEWED)
                        ->whereDate('effective_from', '<=', $checkInDate)
                        ->where(function ($q) use ($checkInDate) {
                            $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $checkInDate);
                        })
                        ->exists();

                    if (! $covered) {
                        throw ValidationException::withMessages([
                            'status' => "Cannot approve statement: unit {$unit->unit_code} on {$checkInDate} is not covered by a reviewed management agreement.",
                        ]);
                    }
                }
            }
        }

        $statement->update(['status' => 'approved', 'approved_by' => $by->id, 'approved_at' => now()]);
    }

    public function markPaid(OwnerStatement $statement, ?string $reference, ?User $by = null): void
    {
        if ($statement->status !== 'approved') {
            throw ValidationException::withMessages(['status' => 'Approve the statement before recording the payout.']);
        }

        DB::transaction(function () use ($statement, $reference, $by) {
            $statement->update(['status' => 'paid', 'paid_at' => now(), 'payment_reference' => $reference]);

            $this->postingService->postPayout($statement, $reference, $by?->id ?? auth()->id());
        });
    }
}
