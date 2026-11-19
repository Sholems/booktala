<?php

namespace Tests\Feature\Finance;

use App\Models\Expense;
use App\Models\FinanceEntry;
use App\Models\FinanceTransaction;
use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\Unit;
use App\Models\User;
use App\Services\OwnerStatementService;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OwnerStatementLedgerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Site $site;

    private Owner $owner;

    private Unit $unit;

    private ManagementAgreement $agreement;

    private OwnerStatementService $statementService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->travelTo(CarbonImmutable::parse('2026-11-15 10:00:00'));

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);
        $this->admin->assignRole('admin');

        $this->site = Site::create([
            'name' => 'Lekki Pearl',
            'address' => '10 Admiralty Way',
            'city' => 'Lekki',
            'state' => 'Lagos',
        ]);

        $this->owner = Owner::create([
            'name' => 'Alhaji Musa',
            'type' => 'individual',
            'is_company_owned' => false,
            'commission_rate' => 20,
            'deduct_expenses' => true,
            'is_active' => true,
        ]);

        $this->unit = Unit::create([
            'unit_code' => 'LP-101',
            'title' => 'Pearl Suite 101',
            'description' => 'Luxury 1 bed',
            'address' => '10 Admiralty Way',
            'city' => 'Lekki',
            'state' => 'Lagos',
            'price_per_night' => 25000,
            'max_guests' => 2,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'toilets' => 1,
            'status' => 'active',
            'site_id' => $this->site->id,
            'owner_id' => $this->owner->id,
        ]);

        $this->agreement = ManagementAgreement::create([
            'unit_id' => $this->unit->id,
            'owner_id' => $this->owner->id,
            'status' => ManagementAgreement::STATUS_REVIEWED,
            'commission_rate' => 20,
            'deduct_expenses' => true,
            'caution_forfeiture_recipient' => 'company',
            'effective_from' => '2026-01-01',
            'effective_to' => null,
            'reviewed_at' => now(),
        ]);

        $this->statementService = app(OwnerStatementService::class);
    }

    private function createStay(array $overrides = []): Reservation
    {
        $guest = User::create([
            'name' => 'Guest '.uniqid(),
            'email' => 'guest'.uniqid().'@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);

        return Reservation::create(array_merge([
            'booking_ref' => Reservation::generateBookingRef(),
            'user_id' => $guest->id,
            'property_id' => $this->unit->id,
            'check_in' => '2026-10-05',
            'check_out' => '2026-10-10',
            'nights' => 5,
            'guests' => 2,
            'subtotal' => 125000,
            'discount_amount' => 0,
            'tax_amount' => 9375,
            'total_amount' => 134375,
            'payment_status' => 'paid',
            'booking_status' => 'checked_out',
            'checked_out_at' => '2026-10-10 11:00:00',
        ], $overrides));
    }

    public function test_draft_statement_contains_only_paid_completed_stays_and_eligible_expenses(): void
    {
        // 1. Eligible stay: paid, checked_out in October
        $validStay = $this->createStay();

        // 2. Unpaid stay in October: excluded
        $this->createStay([
            'payment_status' => 'pending',
            'booking_status' => 'checked_out',
        ]);

        // 3. Cancelled stay in October: excluded
        $this->createStay([
            'payment_status' => 'paid',
            'booking_status' => 'cancelled',
        ]);

        // 4. Stay ending in November: excluded from October
        $this->createStay([
            'check_in' => '2026-10-28',
            'check_out' => '2026-11-02',
            'checked_out_at' => '2026-11-02 10:00:00',
        ]);

        // 5. Eligible owner expense in October
        Expense::create([
            'property_id' => $this->unit->id,
            'category' => 'repairs',
            'description' => 'AC maintenance',
            'amount' => 15000,
            'spent_on' => '2026-10-15',
            'charge_to' => 'owner',
        ]);

        // 6. Company expense in October: excluded
        Expense::create([
            'property_id' => $this->unit->id,
            'category' => 'cleaning',
            'description' => 'General cleaning',
            'amount' => 5000,
            'spent_on' => '2026-10-16',
            'charge_to' => 'company',
        ]);

        $statement = $this->statementService->generate($this->owner, CarbonImmutable::parse('2026-10-01'));

        // Gross = 125,000; Commission (20%) = 25,000; Expenses = 15,000; Net = 85,000
        $this->assertEquals('125000.00', $statement->gross_revenue);
        $this->assertEquals('25000.00', $statement->commission_amount);
        $this->assertEquals('15000.00', $statement->expenses_total);
        $this->assertEquals('85000.00', $statement->net_payout);
        $this->assertSame(5, $statement->nights_booked);
        $this->assertCount(2, $statement->lines); // 1 stay line, 1 expense line
    }

    public function test_statement_approval_blocks_uncovered_agreement_dates_ae6(): void
    {
        // Create unit without an agreement
        $uncoveredUnit = Unit::create([
            'unit_code' => 'LP-102',
            'title' => 'Pearl Suite 102',
            'description' => 'Unagreed suite',
            'address' => '10 Admiralty Way',
            'city' => 'Lekki',
            'state' => 'Lagos',
            'price_per_night' => 20000,
            'max_guests' => 2,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'toilets' => 1,
            'status' => 'active',
            'site_id' => $this->site->id,
            'owner_id' => $this->owner->id,
        ]);

        $this->createStay([
            'property_id' => $uncoveredUnit->id,
            'check_in' => '2026-10-08',
            'check_out' => '2026-10-12',
            'checked_out_at' => '2026-10-12 11:00:00',
        ]);

        $statement = $this->statementService->generate($this->owner, CarbonImmutable::parse('2026-10-01'));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/Cannot approve statement.*LP-102.*2026-10-08.*not covered by a reviewed management agreement/i');

        $this->statementService->approve($statement, $this->admin);
    }

    public function test_statement_approval_succeeds_when_covered_by_reviewed_agreement(): void
    {
        $this->createStay();
        $statement = $this->statementService->generate($this->owner, CarbonImmutable::parse('2026-10-01'));

        $this->statementService->approve($statement, $this->admin);

        $this->assertSame('approved', $statement->fresh()->status);
        $this->assertSame($this->admin->id, $statement->fresh()->approved_by);
        $this->assertNotNull($statement->fresh()->approved_at);
    }

    public function test_marking_paid_records_reference_and_posts_balanced_payout_transaction(): void
    {
        $this->createStay();
        $statement = $this->statementService->generate($this->owner, CarbonImmutable::parse('2026-10-01'));
        $this->statementService->approve($statement, $this->admin);

        $this->statementService->markPaid($statement, 'TRF-PAY-001', $this->admin);

        $fresh = $statement->fresh();
        $this->assertSame('paid', $fresh->status);
        $this->assertSame('TRF-PAY-001', $fresh->payment_reference);
        $this->assertNotNull($fresh->paid_at);

        // Verify journal payout transaction
        $transaction = FinanceTransaction::where('source_type', 'owner_statement')
            ->where('source_id', (string) $statement->id)
            ->where('event_type', 'payout')
            ->firstOrFail();

        $this->assertSame("owner_statement:{$statement->id}:payout", $transaction->idempotency_key);

        $entries = $transaction->entries;
        $this->assertCount(2, $entries);
        $this->assertSame(0, (int) $entries->sum('amount_minor'));

        $payableEntry = $entries->firstWhere('account', FinanceEntry::ACCOUNT_OWNER_PAYABLE);
        $clearingEntry = $entries->firstWhere('account', FinanceEntry::ACCOUNT_PAYOUT_CLEARING);

        $expectedMinor = (int) round(((float) $statement->net_payout) * 100);
        $this->assertSame($expectedMinor, (int) $payableEntry->amount_minor); // Debit liability
        $this->assertSame(-$expectedMinor, (int) $clearingEntry->amount_minor); // Credit clearing
    }

    public function test_approved_statement_is_frozen_against_later_changes(): void
    {
        $this->createStay();
        $statement = $this->statementService->generate($this->owner, CarbonImmutable::parse('2026-10-01'));
        $this->statementService->approve($statement, $this->admin);

        $approvedGross = $statement->gross_revenue;
        $approvedNet = $statement->net_payout;
        $lineCount = $statement->lines()->count();

        // Alter owner commission rate
        $this->owner->update(['commission_rate' => 50]);

        // Attempt to regenerate statement for October
        try {
            $this->statementService->generate($this->owner, CarbonImmutable::parse('2026-10-01'));
            $this->fail('Expected ValidationException when generating for approved statement');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('already approved', $e->getMessage());
        }

        $fresh = $statement->fresh();
        $this->assertSame($approvedGross, $fresh->gross_revenue);
        $this->assertSame($approvedNet, $fresh->net_payout);
        $this->assertSame($lineCount, $fresh->lines()->count());
    }

    public function test_cross_owner_and_cross_site_isolation(): void
    {
        $otherOwner = Owner::create([
            'name' => 'Dr Chukwu',
            'type' => 'individual',
            'is_company_owned' => false,
            'commission_rate' => 15,
            'deduct_expenses' => true,
            'is_active' => true,
        ]);

        $otherUnit = Unit::create([
            'unit_code' => 'LP-201',
            'title' => 'Pearl Suite 201',
            'description' => 'Chukwu Suite',
            'address' => '10 Admiralty Way',
            'city' => 'Lekki',
            'state' => 'Lagos',
            'price_per_night' => 30000,
            'max_guests' => 2,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'toilets' => 1,
            'status' => 'active',
            'site_id' => $this->site->id,
            'owner_id' => $otherOwner->id,
        ]);

        ManagementAgreement::create([
            'unit_id' => $otherUnit->id,
            'owner_id' => $otherOwner->id,
            'status' => ManagementAgreement::STATUS_REVIEWED,
            'commission_rate' => 15,
            'deduct_expenses' => true,
            'caution_forfeiture_recipient' => 'company',
            'effective_from' => '2026-01-01',
            'effective_to' => null,
            'reviewed_at' => now(),
        ]);

        // Stays for both owners in October
        $this->createStay();
        $this->createStay([
            'property_id' => $otherUnit->id,
            'subtotal' => 200000,
        ]);

        $statement = $this->statementService->generate($this->owner, CarbonImmutable::parse('2026-10-01'));

        $this->assertSame('125000.00', $statement->gross_revenue);
        foreach ($statement->lines as $line) {
            $this->assertSame($this->unit->id, $line->property_id);
        }
    }
}
