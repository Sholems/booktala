<?php

namespace App\Services\Finance;

use App\Models\ManagementAgreement;
use App\Models\Unit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class AgreementResolver
{
    public function create(array $attributes): ManagementAgreement
    {
        $start = CarbonImmutable::parse($attributes['effective_from'])->startOfDay();
        $end = isset($attributes['effective_to'])
            ? CarbonImmutable::parse($attributes['effective_to'])->startOfDay()
            : null;

        $this->validateDates($start, $end);
        $this->validateTerms($attributes);

        return DB::transaction(function () use ($attributes, $start, $end) {
            Unit::query()->lockForUpdate()->findOrFail($attributes['unit_id']);
            $this->rejectOverlap((int) $attributes['unit_id'], $start, $end);

            return ManagementAgreement::create([
                ...$attributes,
                'effective_from' => $start->toDateString(),
                'effective_to' => $end?->toDateString(),
                'status' => ManagementAgreement::STATUS_DRAFT,
                'deduct_expenses' => $attributes['deduct_expenses'] ?? true,
                'caution_forfeiture_recipient' => $attributes['caution_forfeiture_recipient'] ?? 'company',
            ]);
        }, 3);
    }

    public function review(ManagementAgreement $agreement, ?User $reviewer = null): ManagementAgreement
    {
        return DB::transaction(function () use ($agreement, $reviewer) {
            Unit::query()->lockForUpdate()->findOrFail($agreement->unit_id);
            $locked = ManagementAgreement::query()->lockForUpdate()->findOrFail($agreement->id);
            $this->rejectOverlap(
                $locked->unit_id,
                CarbonImmutable::instance($locked->effective_from),
                $locked->effective_to ? CarbonImmutable::instance($locked->effective_to) : null,
                $locked->id,
            );

            $locked->update([
                'status' => ManagementAgreement::STATUS_REVIEWED,
                'reviewed_by' => $reviewer?->id,
                'reviewed_at' => now(),
            ]);

            return $locked->refresh();
        }, 3);
    }

    public function retire(ManagementAgreement $agreement): ManagementAgreement
    {
        return DB::transaction(function () use ($agreement) {
            $locked = ManagementAgreement::query()->lockForUpdate()->findOrFail($agreement->id);

            if ($locked->status !== ManagementAgreement::STATUS_REVIEWED) {
                throw new \DomainException('Only reviewed agreements can be retired.');
            }

            $locked->update([
                'status' => ManagementAgreement::STATUS_RETIRED,
            ]);

            return $locked->refresh();
        }, 3);
    }

    public function coverageAudit(?int $siteId = null, ?User $user = null): array
    {
        $unitsQuery = Unit::query()->with(['owner', 'site']);

        if ($user !== null) {
            $unitsQuery->visibleTo($user);
        }

        if ($siteId !== null) {
            $unitsQuery->where('site_id', $siteId);
        }

        // Only externally managed units require management agreements (R1).
        $externalUnits = $unitsQuery->whereHas('owner', fn ($q) => $q->where('is_company_owned', false))->get();

        $uncoveredUnits = [];
        $coveredCount = 0;

        foreach ($externalUnits as $unit) {
            $agreements = ManagementAgreement::query()
                ->where('unit_id', $unit->id)
                ->where('status', ManagementAgreement::STATUS_REVIEWED)
                ->orderBy('effective_from')
                ->get();

            $uncoveredDates = [];
            $reasons = [];

            if ($agreements->isEmpty()) {
                $reasons[] = 'No reviewed management agreement exists for this unit.';
                $uncoveredDates[] = 'All dates';
            } else {
                // Check for reservations lacking coverage on check-in date
                $reservations = $unit->reservations()
                    ->whereIn('booking_status', ['confirmed', 'checked_in', 'checked_out'])
                    ->get();

                foreach ($reservations as $reservation) {
                    $checkInDate = $reservation->check_in->toDateString();
                    $covered = $agreements->contains(function ($agreement) use ($checkInDate) {
                        return $agreement->effective_from->toDateString() <= $checkInDate
                            && ($agreement->effective_to === null || $agreement->effective_to->toDateString() >= $checkInDate);
                    });

                    if (! $covered) {
                        $uncoveredDates[] = $checkInDate." (Booking {$reservation->booking_ref})";
                    }
                }

                // Check if current date is covered
                $today = today()->toDateString();
                $todayCovered = $agreements->contains(function ($agreement) use ($today) {
                    return $agreement->effective_from->toDateString() <= $today
                        && ($agreement->effective_to === null || $agreement->effective_to->toDateString() >= $today);
                });

                if (! $todayCovered) {
                    $reasons[] = 'No reviewed agreement covers current operations today ('.$today.').';
                    if (! in_array($today, $uncoveredDates, true)) {
                        $uncoveredDates[] = $today;
                    }
                }
            }

            if (! empty($uncoveredDates) || ! empty($reasons)) {
                $uncoveredUnits[] = [
                    'unit' => $unit,
                    'owner' => $unit->owner,
                    'site' => $unit->site,
                    'reasons' => $reasons,
                    'uncovered_dates' => array_values(array_unique($uncoveredDates)),
                ];
            } else {
                $coveredCount++;
            }
        }

        return [
            'uncovered_units' => $uncoveredUnits,
            'covered_units_count' => $coveredCount,
            'uncovered_units_count' => count($uncoveredUnits),
            'total_external_units' => $externalUnits->count(),
        ];
    }

    public function resolve(Unit|int $unit, CarbonInterface|string $economicDate): ManagementAgreement
    {
        $unit = $unit instanceof Unit ? $unit : Unit::query()->findOrFail($unit);
        $date = $economicDate instanceof CarbonInterface
            ? $economicDate->toDateString()
            : CarbonImmutable::parse($economicDate)->toDateString();

        $agreement = ManagementAgreement::query()
            ->where('unit_id', $unit->id)
            ->where('status', ManagementAgreement::STATUS_REVIEWED)
            ->whereDate('effective_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            })
            ->orderByDesc('effective_from')
            ->first();

        if (! $agreement) {
            throw new \DomainException("No reviewed management agreement covers unit {$unit->unit_code} on {$date}.");
        }

        return $agreement;
    }

    private function rejectOverlap(int $unitId, CarbonImmutable $start, ?CarbonImmutable $end, ?int $exceptId = null): void
    {
        $query = ManagementAgreement::query()
            ->where('unit_id', $unitId)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->whereDate('effective_from', '<=', ($end ?? CarbonImmutable::parse('9999-12-31'))->toDateString())
            ->where(function ($query) use ($start) {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $start->toDateString());
            });

        if ($query->exists()) {
            throw new \DomainException('This unit already has an agreement covering part of that period.');
        }
    }

    private function validateDates(CarbonImmutable $start, ?CarbonImmutable $end): void
    {
        if ($end && $end->isBefore($start)) {
            throw new \DomainException('The agreement end date cannot be before its start date.');
        }
    }

    private function validateTerms(array $attributes): void
    {
        $commissionRate = (float) ($attributes['commission_rate'] ?? -1);

        if ($commissionRate < 0 || $commissionRate > 100) {
            throw new \DomainException('The commission rate must be between 0 and 100.');
        }

        if (! in_array($attributes['caution_forfeiture_recipient'] ?? 'company', ['company', 'owner'], true)) {
            throw new \DomainException('The caution forfeiture recipient must be company or owner.');
        }
    }
}
