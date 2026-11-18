<?php

namespace Tests\Feature\Finance;

use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\Unit;
use App\Services\Finance\AgreementResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagementAgreementTest extends TestCase
{
    use RefreshDatabase;

    public function test_adjacent_reviewed_agreements_resolve_on_their_date_boundaries(): void
    {
        $unit = Unit::factory()->create();
        $oldOwner = Owner::create(['name' => 'Old Owner', 'type' => 'individual']);
        $newOwner = Owner::create(['name' => 'New Owner', 'type' => 'individual']);
        $resolver = app(AgreementResolver::class);

        $old = $resolver->create([
            'unit_id' => $unit->id,
            'owner_id' => $oldOwner->id,
            'commission_rate' => 20,
            'deduct_expenses' => true,
            'effective_from' => '2026-01-01',
            'effective_to' => '2026-12-31',
        ]);
        $resolver->review($old);

        $new = $resolver->create([
            'unit_id' => $unit->id,
            'owner_id' => $newOwner->id,
            'commission_rate' => 15,
            'deduct_expenses' => false,
            'effective_from' => '2027-01-01',
        ]);
        $resolver->review($new);

        $this->assertTrue($resolver->resolve($unit, '2026-12-31')->is($old));
        $this->assertTrue($resolver->resolve($unit, '2027-01-01')->is($new));
    }

    public function test_overlapping_ranges_for_the_same_unit_are_rejected_atomically(): void
    {
        $unit = Unit::factory()->create();
        $owner = Owner::create(['name' => 'Owner', 'type' => 'individual']);
        $resolver = app(AgreementResolver::class);

        $resolver->create([
            'unit_id' => $unit->id,
            'owner_id' => $owner->id,
            'commission_rate' => 20,
            'effective_from' => '2026-01-01',
            'effective_to' => '2026-12-31',
        ]);

        try {
            $resolver->create([
                'unit_id' => $unit->id,
                'owner_id' => $owner->id,
                'commission_rate' => 18,
                'effective_from' => '2026-06-01',
                'effective_to' => '2027-05-31',
            ]);
            $this->fail('An overlapping agreement should be rejected.');
        } catch (\DomainException $exception) {
            $this->assertSame('This unit already has an agreement covering part of that period.', $exception->getMessage());
        }

        $this->assertSame(1, ManagementAgreement::where('unit_id', $unit->id)->count());
    }

    public function test_identical_periods_are_allowed_for_different_units(): void
    {
        $firstUnit = Unit::factory()->create();
        $secondUnit = Unit::factory()->create();
        $owner = Owner::create(['name' => 'Owner', 'type' => 'individual']);
        $resolver = app(AgreementResolver::class);
        $attributes = [
            'owner_id' => $owner->id,
            'commission_rate' => 20,
            'effective_from' => '2026-01-01',
            'effective_to' => '2026-12-31',
        ];

        $resolver->create(['unit_id' => $firstUnit->id, ...$attributes]);
        $resolver->create(['unit_id' => $secondUnit->id, ...$attributes]);

        $this->assertSame(2, ManagementAgreement::count());
    }

    public function test_only_reviewed_agreements_resolve_and_uncovered_dates_fail_clearly(): void
    {
        $unit = Unit::factory()->create();
        $owner = Owner::create(['name' => 'Owner', 'type' => 'individual']);
        $resolver = app(AgreementResolver::class);
        $agreement = $resolver->create([
            'unit_id' => $unit->id,
            'owner_id' => $owner->id,
            'commission_rate' => 20,
            'effective_from' => '2026-03-01',
        ]);

        try {
            $resolver->resolve($unit, '2026-03-01');
            $this->fail('A draft agreement should not resolve.');
        } catch (\DomainException $exception) {
            $this->assertSame(
                "No reviewed management agreement covers unit {$unit->unit_code} on 2026-03-01.",
                $exception->getMessage(),
            );
        }

        $reviewed = $resolver->review($agreement);

        $this->assertTrue($resolver->resolve($unit, '2026-03-01')->is($reviewed));
    }

    public function test_end_date_cannot_precede_start_date(): void
    {
        $unit = Unit::factory()->create();
        $owner = Owner::create(['name' => 'Owner', 'type' => 'individual']);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('The agreement end date cannot be before its start date.');

        app(AgreementResolver::class)->create([
            'unit_id' => $unit->id,
            'owner_id' => $owner->id,
            'commission_rate' => 20,
            'effective_from' => '2026-12-31',
            'effective_to' => '2026-01-01',
        ]);
    }

    public function test_reviewed_agreement_terms_cannot_be_changed_or_deleted(): void
    {
        $unit = Unit::factory()->create();
        $owner = Owner::create(['name' => 'Owner', 'type' => 'individual']);
        $resolver = app(AgreementResolver::class);
        $agreement = $resolver->create([
            'unit_id' => $unit->id,
            'owner_id' => $owner->id,
            'commission_rate' => 20,
            'effective_from' => '2026-01-01',
        ]);
        $agreement = $resolver->review($agreement);

        foreach ([
            fn () => $agreement->update(['commission_rate' => 10]),
            fn () => $agreement->delete(),
        ] as $mutation) {
            try {
                $mutation();
                $this->fail('Reviewed agreements must preserve their historical terms.');
            } catch (\LogicException $exception) {
                $this->assertStringContainsString('Reviewed management agreement', $exception->getMessage());
            }
        }

        $this->assertDatabaseHas('management_agreements', [
            'id' => $agreement->id,
            'commission_rate' => 20,
        ]);
    }
}
