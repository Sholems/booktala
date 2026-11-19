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

class TaxComplianceReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $guest;

    private Site $siteLagos;

    private Site $siteAbuja;

    private Owner $individualOwnerLagos;

    private Owner $corporateOwnerAbuja;

    private Unit $unitLagos;

    private Unit $unitAbuja;

    private LedgerPostingService $postingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->travelTo(CarbonImmutable::parse('2026-11-15 10:00:00'));

        $this->admin = User::create([
            'name' => 'Tax Compliance Officer',
            'email' => 'tax@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);
        $this->admin->assignRole('super_admin');

        $this->guest = User::create([
            'name' => 'Kayode Williams',
            'email' => 'kayode@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);
        $this->guest->assignRole('customer');

        $this->siteLagos = Site::create([
            'name' => 'Victoria Island Heights',
            'address' => '12 Adeola Odeku',
            'city' => 'Victoria Island',
            'state' => 'Lagos',
        ]);

        $this->siteAbuja = Site::create([
            'name' => 'Maitama Grand Residences',
            'address' => '24 Gana Street',
            'city' => 'Maitama',
            'state' => 'Abuja',
        ]);

        $this->individualOwnerLagos = Owner::create([
            'name' => 'Chief Olumide Balogun',
            'type' => 'individual',
            'tax_id' => 'JTB-LAG-987654',
            'is_company_owned' => false,
            'commission_rate' => 20,
            'deduct_expenses' => true,
            'is_active' => true,
        ]);

        $this->corporateOwnerAbuja = Owner::create([
            'name' => 'Aso Capital Properties Ltd',
            'type' => 'company',
            'tax_id' => 'FIRS-TIN-11223344',
            'is_company_owned' => false,
            'commission_rate' => 15,
            'deduct_expenses' => true,
            'is_active' => true,
        ]);

        $this->unitLagos = Unit::create([
            'unit_code' => 'VI-201',
            'title' => 'Balogun Royal Suite',
            'description' => 'Luxury 3-bed',
            'address' => '12 Adeola Odeku',
            'city' => 'Victoria Island',
            'state' => 'Lagos',
            'price_per_night' => 100000,
            'max_guests' => 4,
            'bedrooms' => 3,
            'bathrooms' => 3,
            'toilets' => 3,
            'status' => 'active',
            'site_id' => $this->siteLagos->id,
            'owner_id' => $this->individualOwnerLagos->id,
        ]);

        $this->unitAbuja = Unit::create([
            'unit_code' => 'MT-301',
            'title' => 'Maitama Presidential Flat',
            'description' => 'Diplomatic residence',
            'address' => '24 Gana Street',
            'city' => 'Maitama',
            'state' => 'Abuja',
            'price_per_night' => 200000,
            'max_guests' => 4,
            'bedrooms' => 2,
            'bathrooms' => 2,
            'toilets' => 2,
            'status' => 'active',
            'site_id' => $this->siteAbuja->id,
            'owner_id' => $this->corporateOwnerAbuja->id,
        ]);

        ManagementAgreement::create([
            'unit_id' => $this->unitLagos->id,
            'owner_id' => $this->individualOwnerLagos->id,
            'agreement_model' => 'commission',
            'commission_rate' => 20,
            'deduct_expenses' => true,
            'effective_from' => '2026-01-01',
            'status' => 'reviewed',
            'reviewed_at' => now(),
            'reviewed_by' => $this->admin->id,
        ]);

        ManagementAgreement::create([
            'unit_id' => $this->unitAbuja->id,
            'owner_id' => $this->corporateOwnerAbuja->id,
            'agreement_model' => 'commission',
            'commission_rate' => 15,
            'deduct_expenses' => true,
            'effective_from' => '2026-01-01',
            'status' => 'reviewed',
            'reviewed_at' => now(),
            'reviewed_by' => $this->admin->id,
        ]);

        $this->postingService = app(LedgerPostingService::class);
    }

    public function test_guest_cannot_access_tax_compliance(): void
    {
        $response = $this->get(route('admin.finance.tax.index'));
        $response->assertRedirect('/login');
    }

    public function test_unauthorized_staff_cannot_access_tax_compliance(): void
    {
        $staff = User::create([
            'name' => 'Staff Member',
            'email' => 'staff@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);
        $staff->assignRole('staff');

        $response = $this->actingAs($staff)->get(route('admin.finance.tax.index'));
        $response->assertForbidden();
    }

    public function test_tax_compliance_report_computes_vat_and_wht_by_jurisdiction(): void
    {
        // Reservation 1: Lagos, NGN 200,000 (20% comm = 40,000; Owner gross = 200,000)
        $resLagos = Reservation::create([
            'booking_ref' => 'BK-TAX-LAGOS',
            'user_id' => $this->guest->id,
            'property_id' => $this->unitLagos->id,
            'check_in' => '2026-11-01',
            'check_out' => '2026-11-03',
            'checked_out_at' => '2026-11-03 11:00:00',
            'nights' => 2,
            'guests' => 2,
            'subtotal' => 200000,
            'discount_amount' => 0,
            'processing_fee' => 0,
            'caution_fee' => 0,
            'tax_amount' => 0,
            'total_amount' => 200000,
            'booking_status' => 'checked_out',
            'payment_status' => 'paid',
        ]);

        $paymentLagos = Payment::create([
            'reservation_id' => $resLagos->id,
            'reference' => 'PAY-TAX-LAG',
            'paystack_reference' => 'PST-TAX-LAG',
            'amount' => 200000,
            'payment_method' => 'paystack',
            'paystack_status' => 'successful',
            'payment_type' => 'reservation',
            'collected_at' => '2026-11-01 10:00:00',
        ]);

        $this->postingService->postCollection($paymentLagos);
        $this->postingService->postStayCompletion($resLagos);

        // Reservation 2: Abuja, NGN 400,000 (15% comm = 60,000; Owner gross = 400,000)
        $resAbuja = Reservation::create([
            'booking_ref' => 'BK-TAX-ABUJA',
            'user_id' => $this->guest->id,
            'property_id' => $this->unitAbuja->id,
            'check_in' => '2026-11-05',
            'check_out' => '2026-11-07',
            'checked_out_at' => '2026-11-07 10:30:00',
            'nights' => 2,
            'guests' => 2,
            'subtotal' => 400000,
            'discount_amount' => 0,
            'processing_fee' => 0,
            'caution_fee' => 0,
            'tax_amount' => 0,
            'total_amount' => 400000,
            'booking_status' => 'checked_out',
            'payment_status' => 'paid',
        ]);

        $paymentAbuja = Payment::create([
            'reservation_id' => $resAbuja->id,
            'reference' => 'PAY-TAX-ABJ',
            'paystack_reference' => 'PST-TAX-ABJ',
            'amount' => 400000,
            'payment_method' => 'paystack',
            'paystack_status' => 'successful',
            'payment_type' => 'reservation',
            'collected_at' => '2026-11-05 09:00:00',
        ]);

        $this->postingService->postCollection($paymentAbuja);
        $this->postingService->postStayCompletion($resAbuja);

        // Total accommodation revenue: 600,000 NGN
        // Output VAT (7.5%): 45,000 NGN
        // Landlord Rental WHT (10%):
        //   - Chief Olumide Balogun (Lagos individual): 10% of 200,000 = 20,000 NGN -> LIRS
        //   - Aso Capital Properties Ltd (Abuja corporate): 10% of 400,000 = 40,000 NGN -> FIRS

        $response = $this->actingAs($this->admin)->get(route('admin.finance.tax.index'));

        $response->assertOk();
        $response->assertSee('Tax & WHT Compliance Reports');
        $response->assertSee('₦45,000.00'); // VAT
        $response->assertSee('Chief Olumide Balogun');
        $response->assertSee('JTB-LAG-987654');
        $response->assertSee('LIRS (Lagos State Internal Revenue Service)');
        $response->assertSee('₦20,000.00'); // Balogun rental WHT
        $response->assertSee('Aso Capital Properties Ltd');
        $response->assertSee('FIRS-TIN-11223344');
        $response->assertSee('₦40,000.00'); // Aso corporate rental WHT
    }

    public function test_export_tax_compliance_schedule_csv(): void
    {
        $resLagos = Reservation::create([
            'booking_ref' => 'BK-EXPORT-CSV',
            'user_id' => $this->guest->id,
            'property_id' => $this->unitLagos->id,
            'check_in' => '2026-11-02',
            'check_out' => '2026-11-04',
            'checked_out_at' => '2026-11-04 12:00:00',
            'nights' => 2,
            'guests' => 2,
            'subtotal' => 100000,
            'discount_amount' => 0,
            'processing_fee' => 0,
            'caution_fee' => 0,
            'tax_amount' => 0,
            'total_amount' => 100000,
            'booking_status' => 'checked_out',
            'payment_status' => 'paid',
        ]);

        $paymentLagos = Payment::create([
            'reservation_id' => $resLagos->id,
            'reference' => 'PAY-EXP-CSV',
            'paystack_reference' => 'PST-EXP-CSV',
            'amount' => 100000,
            'payment_method' => 'paystack',
            'paystack_status' => 'successful',
            'payment_type' => 'reservation',
            'collected_at' => '2026-11-02 10:00:00',
        ]);

        $this->postingService->postCollection($paymentLagos);
        $this->postingService->postStayCompletion($resLagos);

        $response = $this->actingAs($this->admin)->get(route('admin.finance.tax.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->getContent();
        $this->assertStringContainsString('BOOKTALA STATUTORY TAX & WITHHOLDING TAX (WHT) COMPLIANCE REPORT', $csv);
        $this->assertStringContainsString('--- SECTION 1: STATUTORY TAX LIABILITIES SUMMARY ---', $csv);
        $this->assertStringContainsString('--- SECTION 2: LANDLORD RENTAL WHT AUDIT SCHEDULE ---', $csv);
        $this->assertStringContainsString('--- SECTION 3: COMMISSION WHT SCHEDULE ---', $csv);
        $this->assertStringContainsString('Chief Olumide Balogun', $csv);
        $this->assertStringContainsString('JTB-LAG-987654', $csv);
        $this->assertStringContainsString('LIRS', $csv);
    }
}
