<?php

namespace Tests\Feature\Finance;

use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\Unit;
use App\Models\User;
use App\Services\Finance\LedgerPostingService;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneralLedgerExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $guest;

    private Site $site;

    private Owner $owner;

    private Unit $unit;

    private ManagementAgreement $agreement;

    private LedgerPostingService $postingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->travelTo(CarbonImmutable::parse('2026-11-15 10:00:00'));

        $this->admin = User::create([
            'name' => 'Finance Director',
            'email' => 'finance@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);
        $this->admin->assignRole('super_admin');

        $this->guest = User::create([
            'name' => 'Tunde Adeyemi',
            'email' => 'tunde@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);
        $this->guest->assignRole('customer');

        $this->site = Site::create([
            'name' => 'Lekki Coastal Suites',
            'address' => '10 Admiralty Way',
            'city' => 'Lekki',
            'state' => 'Lagos',
        ]);

        $this->owner = Owner::create([
            'name' => 'Eko Investments Ltd',
            'type' => 'company',
            'tax_id' => '10293847-0001',
            'is_company_owned' => false,
            'commission_rate' => 20,
            'deduct_expenses' => true,
            'is_active' => true,
        ]);

        $this->unit = Unit::create([
            'unit_code' => 'LK-101',
            'title' => 'Ocean View Penthouse',
            'description' => 'Luxury 2-bed suite',
            'address' => '10 Admiralty Way',
            'city' => 'Lekki',
            'state' => 'Lagos',
            'price_per_night' => 100000,
            'max_guests' => 4,
            'bedrooms' => 2,
            'bathrooms' => 2,
            'toilets' => 2,
            'status' => 'active',
            'site_id' => $this->site->id,
            'owner_id' => $this->owner->id,
        ]);

        $this->agreement = ManagementAgreement::create([
            'unit_id' => $this->unit->id,
            'owner_id' => $this->owner->id,
            'agreement_model' => 'commission',
            'commission_rate' => 20,
            'deduct_expenses' => true,
            'effective_from' => '2026-01-01',
            'status' => 'reviewed',
            'reviewed_at' => now(),
            'reviewed_by' => $this->admin->id,
        ]);

        $this->postingService = app(LedgerPostingService::class);
    }

    public function test_guest_cannot_access_ledger(): void
    {
        $response = $this->get(route('admin.finance.ledger.index'));
        $response->assertRedirect('/login');
    }

    public function test_user_without_permission_cannot_access_ledger(): void
    {
        $staff = User::create([
            'name' => 'Basic Staff',
            'email' => 'cleaner@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);
        $staff->assignRole('staff');

        $response = $this->actingAs($staff)->get(route('admin.finance.ledger.index'));
        $response->assertForbidden();
    }

    public function test_finance_admin_can_view_ledger_with_balanced_summary(): void
    {
        $reservation = Reservation::create([
            'booking_ref' => 'BK-TEST-GL1',
            'user_id' => $this->guest->id,
            'property_id' => $this->unit->id,
            'check_in' => '2026-11-10',
            'check_out' => '2026-11-12',
            'nights' => 2,
            'guests' => 2,
            'subtotal' => 200000,
            'discount_amount' => 0,
            'processing_fee' => 0,
            'caution_fee' => 0,
            'tax_amount' => 0,
            'total_amount' => 200000,
            'booking_status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'reference' => 'PAY-GL-1001',
            'paystack_reference' => 'PST-GL-1001',
            'amount' => 200000,
            'payment_method' => 'paystack',
            'paystack_status' => 'successful',
            'payment_type' => 'reservation',
            'collected_at' => '2026-11-10 12:00:00',
        ]);

        $tx = $this->postingService->postCollection($payment);

        $response = $this->actingAs($this->admin)->get(route('admin.finance.ledger.index'));

        $response->assertOk();
        $response->assertSee('Statutory General Ledger');
        $response->assertSee('Balanced (Zero-Sum)');
        $response->assertSee('₦200,000.00');
        $response->assertSee('Collection');
        $response->assertSee($tx->reference);
    }

    public function test_export_standard_csv(): void
    {
        $reservation = Reservation::create([
            'booking_ref' => 'BK-TEST-CSV',
            'user_id' => $this->guest->id,
            'property_id' => $this->unit->id,
            'check_in' => '2026-11-10',
            'check_out' => '2026-11-12',
            'nights' => 2,
            'guests' => 2,
            'subtotal' => 150000,
            'discount_amount' => 0,
            'processing_fee' => 0,
            'caution_fee' => 0,
            'tax_amount' => 0,
            'total_amount' => 150000,
            'booking_status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'reference' => 'PAY-CSV-150',
            'paystack_reference' => 'PST-CSV-150',
            'amount' => 150000,
            'payment_method' => 'paystack',
            'paystack_status' => 'successful',
            'payment_type' => 'reservation',
            'collected_at' => '2026-11-10 12:00:00',
        ]);

        $this->postingService->postCollection($payment);

        $response = $this->actingAs($this->admin)->get(route('admin.finance.ledger.export', ['format' => 'standard']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->getContent();
        $this->assertStringContainsString('Transaction Date', $content);
        $this->assertStringContainsString('Reference', $content);
        $this->assertStringContainsString('Account Code', $content);
        $this->assertStringContainsString('Payment Gateway Clearing (Paystack)', $content);
        $this->assertStringContainsString('150000.00', $content);
        $this->assertStringContainsString('Ocean View Penthouse', $content);
        $this->assertStringContainsString('Eko Investments Ltd', $content);
    }

    public function test_export_quickbooks_journal_csv(): void
    {
        $reservation = Reservation::create([
            'booking_ref' => 'BK-TEST-QBO',
            'user_id' => $this->guest->id,
            'property_id' => $this->unit->id,
            'check_in' => '2026-11-10',
            'check_out' => '2026-11-12',
            'nights' => 2,
            'guests' => 2,
            'subtotal' => 80000,
            'discount_amount' => 0,
            'processing_fee' => 0,
            'caution_fee' => 0,
            'tax_amount' => 0,
            'total_amount' => 80000,
            'booking_status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'reference' => 'PAY-QBO-80',
            'paystack_reference' => 'PST-QBO-80',
            'amount' => 80000,
            'payment_method' => 'paystack',
            'paystack_status' => 'successful',
            'payment_type' => 'reservation',
            'collected_at' => '2026-11-10 12:00:00',
        ]);

        $this->postingService->postCollection($payment);

        $response = $this->actingAs($this->admin)->get(route('admin.finance.ledger.export', ['format' => 'quickbooks']));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('*JournalNo,*JournalDate,*Account,*Debit,*Credit,Description,Name,Class,Location', $content);
        $this->assertStringContainsString('1030 - Payment Gateway Clearing (Paystack)', $content);
        $this->assertStringContainsString('80000.00', $content);
        $this->assertStringContainsString('Lekki Coastal Suites', $content);
        $this->assertStringContainsString('Ocean View Penthouse', $content);
    }

    public function test_export_xero_manual_journal_csv(): void
    {
        $reservation = Reservation::create([
            'booking_ref' => 'BK-TEST-XERO',
            'user_id' => $this->guest->id,
            'property_id' => $this->unit->id,
            'check_in' => '2026-11-10',
            'check_out' => '2026-11-12',
            'nights' => 2,
            'guests' => 2,
            'subtotal' => 120000,
            'discount_amount' => 0,
            'processing_fee' => 0,
            'caution_fee' => 0,
            'tax_amount' => 0,
            'total_amount' => 120000,
            'booking_status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'reference' => 'PAY-XERO-120',
            'paystack_reference' => 'PST-XERO-120',
            'amount' => 120000,
            'payment_method' => 'paystack',
            'paystack_status' => 'successful',
            'payment_type' => 'reservation',
            'collected_at' => '2026-11-10 12:00:00',
        ]);

        $this->postingService->postCollection($payment);

        $response = $this->actingAs($this->admin)->get(route('admin.finance.ledger.export', ['format' => 'xero']));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('*Narration,*Date,*Description,*AccountCode,*TaxType,*LineAmount,TrackingName1,TrackingOption1,TrackingName2,TrackingOption2', $content);
        $this->assertStringContainsString('1030', $content);
        $this->assertStringContainsString('120000.00', $content);
        $this->assertStringContainsString('Lekki Coastal Suites', $content);
        $this->assertStringContainsString('Ocean View Penthouse', $content);
    }

    public function test_transaction_inspection_show_endpoint(): void
    {
        $reservation = Reservation::create([
            'booking_ref' => 'BK-TEST-SHOW',
            'user_id' => $this->guest->id,
            'property_id' => $this->unit->id,
            'check_in' => '2026-11-10',
            'check_out' => '2026-11-12',
            'nights' => 2,
            'guests' => 2,
            'subtotal' => 75000,
            'discount_amount' => 0,
            'processing_fee' => 0,
            'caution_fee' => 0,
            'tax_amount' => 0,
            'total_amount' => 75000,
            'booking_status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'reference' => 'PAY-SHOW-75',
            'paystack_reference' => 'PST-SHOW-75',
            'amount' => 75000,
            'payment_method' => 'paystack',
            'paystack_status' => 'successful',
            'payment_type' => 'reservation',
            'collected_at' => '2026-11-10 12:00:00',
        ]);

        $tx = $this->postingService->postCollection($payment);

        $response = $this->actingAs($this->admin)->get(route('admin.finance.ledger.show', $tx));

        $response->assertOk();
        $response->assertJsonPath('transaction.id', $tx->id);
        $response->assertJsonPath('transaction.reference', $tx->reference);
        $response->assertJsonCount(2, 'transaction.entries');
    }
}
