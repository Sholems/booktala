<?php

namespace Tests\Feature\Finance;

use App\Models\Expense;
use App\Models\FinanceEntry;
use App\Models\FinanceTransaction;
use App\Models\Guest;
use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\Unit;
use App\Services\Finance\AgreementResolver;
use App\Services\Finance\LedgerReconciliationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerBackfillTest extends TestCase
{
    use RefreshDatabase;

    private Site $site;

    private Owner $owner;

    private Unit $unit;

    private ManagementAgreement $agreement;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->site = Site::create([
            'name' => 'Ikoyi Terraces',
            'address' => '5 Kingsway Road',
            'city' => 'Ikoyi',
            'state' => 'Lagos',
        ]);

        $this->owner = Owner::create([
            'name' => 'Mrs. Danjuma',
            'type' => 'individual',
            'is_company_owned' => false,
        ]);

        $this->unit = Unit::factory()->create([
            'title' => 'Terrace 101',
            'site_id' => $this->site->id,
            'owner_id' => $this->owner->id,
            'price_per_night' => 40000,
        ]);

        $resolver = app(AgreementResolver::class);
        $this->agreement = $resolver->create([
            'unit_id' => $this->unit->id,
            'owner_id' => $this->owner->id,
            'commission_rate' => 15.00,
            'effective_from' => '2026-01-01',
        ]);
        $resolver->review($this->agreement);
    }

    public function test_running_backfill_twice_produces_identical_counts_without_duplicates(): void
    {
        $guest = Guest::create(['name' => 'Historical Guest', 'email' => 'history@example.com', 'phone' => '08021110000']);
        $reservation = Reservation::create([
            'booking_ref' => 'WPA-HIST-01',
            'guest_id' => $guest->id,
            'property_id' => $this->unit->id,
            'check_in' => '2026-02-01',
            'check_out' => '2026-02-03',
            'nights' => 2,
            'guests' => 1,
            'subtotal' => 80000,
            'tax_amount' => 6000,
            'processing_fee' => 2000,
            'caution_fee' => 20000,
            'total_amount' => 108000,
            'payment_status' => 'paid',
            'booking_status' => 'checked_out',
        ]);

        Payment::create([
            'reservation_id' => $reservation->id,
            'amount' => 108000,
            'currency' => 'NGN',
            'provider' => 'paystack',
            'reference' => 'PAY-HIST-01',
            'paystack_reference' => 'PAY-HIST-01',
            'paystack_status' => 'success',
            'payment_method' => 'card',
            'type' => 'payment',
            'collected_at' => '2026-02-01 10:00:00',
        ]);

        Expense::create([
            'property_id' => $this->unit->id,
            'category' => 'cleaning',
            'description' => 'Deep cleaning',
            'amount' => 8000,
            'spent_on' => '2026-02-02',
            'charge_to' => 'owner',
        ]);

        $service = app(LedgerReconciliationService::class);

        // First run
        $first = $service->backfill(['chunk' => 10]);
        $txCountFirst = FinanceTransaction::count();
        $entryCountFirst = FinanceEntry::count();

        $this->assertGreaterThan(0, $txCountFirst);

        // Second run
        $second = $service->backfill(['chunk' => 10]);
        $txCountSecond = FinanceTransaction::count();
        $entryCountSecond = FinanceEntry::count();

        $this->assertSame($txCountFirst, $txCountSecond);
        $this->assertSame($entryCountFirst, $entryCountSecond);

        // Reconciliation should pass completely
        $recon = $service->reconcile(['unit_id' => $this->unit->id]);
        $this->assertTrue($recon['passed']);
        $this->assertEmpty($recon['differences']);
        $this->assertEmpty($recon['unresolved']);
    }

    public function test_records_without_agreement_appear_in_unresolved_output_and_block_reconciliation(): void
    {
        // Second external unit without any agreement
        $secondOwner = Owner::create(['name' => 'Uncovered Owner', 'type' => 'individual', 'is_company_owned' => false]);
        $uncoveredUnit = Unit::factory()->create([
            'site_id' => $this->site->id,
            'owner_id' => $secondOwner->id,
            'price_per_night' => 30000,
        ]);

        $guest = Guest::create(['name' => 'Uncovered Guest', 'email' => 'uncovered@example.com', 'phone' => '08022223333']);
        $reservation = Reservation::create([
            'booking_ref' => 'WPA-UNCOVERED-01',
            'guest_id' => $guest->id,
            'property_id' => $uncoveredUnit->id,
            'check_in' => '2026-03-01',
            'check_out' => '2026-03-02',
            'nights' => 1,
            'guests' => 1,
            'subtotal' => 30000,
            'tax_amount' => 2250,
            'processing_fee' => 2000,
            'caution_fee' => 20000,
            'total_amount' => 54250,
            'payment_status' => 'paid',
            'booking_status' => 'checked_out',
        ]);

        Payment::create([
            'reservation_id' => $reservation->id,
            'amount' => 54250,
            'currency' => 'NGN',
            'provider' => 'paystack',
            'reference' => 'PAY-UNCOVERED-01',
            'paystack_reference' => 'PAY-UNCOVERED-01',
            'paystack_status' => 'success',
            'payment_method' => 'card',
            'type' => 'payment',
        ]);

        $service = app(LedgerReconciliationService::class);
        $backfill = $service->backfill(['unit_id' => $uncoveredUnit->id]);

        $this->assertGreaterThan(0, $backfill['unresolved_count']);
        $this->assertSame('payment', $backfill['unresolved'][0]['source_type']);

        $recon = $service->reconcile(['unit_id' => $uncoveredUnit->id]);
        $this->assertFalse($recon['passed']);
        $this->assertNotEmpty($recon['unresolved']);
    }

    public function test_reconciliation_identifies_category_discrepancy(): void
    {
        $guest = Guest::create(['name' => 'Discrepancy Guest', 'email' => 'disc@example.com', 'phone' => '08023334444']);
        $reservation = Reservation::create([
            'booking_ref' => 'WPA-DISC-01',
            'guest_id' => $guest->id,
            'property_id' => $this->unit->id,
            'check_in' => '2026-04-01',
            'check_out' => '2026-04-02',
            'nights' => 1,
            'guests' => 1,
            'subtotal' => 40000,
            'tax_amount' => 3000,
            'processing_fee' => 2000,
            'caution_fee' => 20000,
            'total_amount' => 65000,
            'payment_status' => 'paid',
            'booking_status' => 'checked_in',
        ]);

        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'amount' => 65000,
            'currency' => 'NGN',
            'provider' => 'offline',
            'reference' => 'DISC-PAY-01',
            'paystack_reference' => 'DISC-PAY-01',
            'paystack_status' => 'success',
            'payment_method' => 'cash',
            'type' => 'payment',
        ]);

        $service = app(LedgerReconciliationService::class);
        $service->backfill(['unit_id' => $this->unit->id]);

        // Manually introduce a 1-kobo drift on the payment source record without altering the journal
        $payment->update(['amount' => 65000.01]);

        $recon = $service->reconcile(['unit_id' => $this->unit->id]);
        $this->assertFalse($recon['passed']);
        $this->assertCount(1, $recon['differences']);
        $this->assertSame('collection_mismatch', $recon['differences'][0]['type']);
        $this->assertSame(1, $recon['differences'][0]['difference_minor']);
    }

    public function test_site_scoped_reconciliation_totals_equal_sum_of_unit_totals(): void
    {
        $unit2 = Unit::factory()->create([
            'site_id' => $this->site->id,
            'owner_id' => $this->owner->id,
            'price_per_night' => 25000,
        ]);

        $resolver = app(AgreementResolver::class);
        $agreement2 = $resolver->create([
            'unit_id' => $unit2->id,
            'owner_id' => $this->owner->id,
            'commission_rate' => 15.00,
            'effective_from' => '2026-01-01',
        ]);
        $resolver->review($agreement2);

        Expense::create([
            'property_id' => $this->unit->id,
            'category' => 'cleaning',
            'description' => 'Unit 1 cleaning',
            'amount' => 5000,
            'spent_on' => '2026-05-01',
            'charge_to' => 'owner',
        ]);

        Expense::create([
            'property_id' => $unit2->id,
            'category' => 'cleaning',
            'description' => 'Unit 2 cleaning',
            'amount' => 7000,
            'spent_on' => '2026-05-01',
            'charge_to' => 'owner',
        ]);

        $service = app(LedgerReconciliationService::class);
        $service->backfill(['site_id' => $this->site->id]);

        $siteRecon = $service->reconcile(['site_id' => $this->site->id]);
        $this->assertTrue($siteRecon['passed']);

        $unit1Recon = $service->reconcile(['unit_id' => $this->unit->id]);
        $unit2Recon = $service->reconcile(['unit_id' => $unit2->id]);

        $sumExpensesMinor = $unit1Recon['totals']['source_expenses_minor'] + $unit2Recon['totals']['source_expenses_minor'];
        $this->assertSame($siteRecon['totals']['source_expenses_minor'], $sumExpensesMinor);
        $this->assertSame(1200000, $siteRecon['totals']['source_expenses_minor']);
    }
}
