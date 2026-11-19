<?php

namespace Tests\Feature\Finance;

use App\Models\Expense;
use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\Unit;
use App\Models\User;
use App\Services\Finance\LedgerPostingService;
use App\Services\Finance\OwnerFinanceQueryService;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerFinanceDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Site $siteA;

    private Site $siteB;

    private Owner $ownerA;

    private User $ownerUserA;

    private Owner $ownerB;

    private User $ownerUserB;

    private Unit $unitA1;

    private Unit $unitA2;

    private Unit $unitB1;

    private LedgerPostingService $postingService;

    private OwnerFinanceQueryService $financeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->travelTo(CarbonImmutable::parse('2026-11-15 10:00:00'));

        $this->siteA = Site::create([
            'name' => 'Victoria Island Heights',
            'address' => '1 Adeola Odeku',
            'city' => 'Victoria Island',
            'state' => 'Lagos',
        ]);

        $this->siteB = Site::create([
            'name' => 'Ikoyi Terraces',
            'address' => '5 Alexander Road',
            'city' => 'Ikoyi',
            'state' => 'Lagos',
        ]);

        // Owner A
        $this->ownerUserA = User::create([
            'name' => 'Owner Alpha',
            'email' => 'alpha@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);
        $this->ownerUserA->assignRole('owner');

        $this->ownerA = Owner::create([
            'name' => 'Alpha Holdings',
            'type' => 'individual',
            'is_company_owned' => false,
            'commission_rate' => 20,
            'deduct_expenses' => true,
            'is_active' => true,
            'user_id' => $this->ownerUserA->id,
        ]);

        // Owner B
        $this->ownerUserB = User::create([
            'name' => 'Owner Beta',
            'email' => 'beta@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);
        $this->ownerUserB->assignRole('owner');

        $this->ownerB = Owner::create([
            'name' => 'Beta Properties',
            'type' => 'individual',
            'is_company_owned' => false,
            'commission_rate' => 15,
            'deduct_expenses' => true,
            'is_active' => true,
            'user_id' => $this->ownerUserB->id,
        ]);

        // Unit A1 at Site A
        $this->unitA1 = Unit::create([
            'unit_code' => 'VI-101',
            'title' => 'Alpha Penthouse VI',
            'description' => 'Luxury',
            'address' => '1 Adeola Odeku',
            'city' => 'Victoria Island',
            'state' => 'Lagos',
            'price_per_night' => 50000,
            'max_guests' => 2,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'toilets' => 1,
            'status' => 'active',
            'site_id' => $this->siteA->id,
            'owner_id' => $this->ownerA->id,
        ]);

        // Unit A2 at Site B
        $this->unitA2 = Unit::create([
            'unit_code' => 'IK-201',
            'title' => 'Alpha Villa Ikoyi',
            'description' => 'Luxury villa',
            'address' => '5 Alexander Road',
            'city' => 'Ikoyi',
            'state' => 'Lagos',
            'price_per_night' => 80000,
            'max_guests' => 4,
            'bedrooms' => 2,
            'bathrooms' => 2,
            'toilets' => 2,
            'status' => 'active',
            'site_id' => $this->siteB->id,
            'owner_id' => $this->ownerA->id,
        ]);

        // Unit B1 at Site A (shared site with Owner A)
        $this->unitB1 = Unit::create([
            'unit_code' => 'VI-102',
            'title' => 'Beta Suite VI',
            'description' => 'Deluxe suite',
            'address' => '1 Adeola Odeku',
            'city' => 'Victoria Island',
            'state' => 'Lagos',
            'price_per_night' => 40000,
            'max_guests' => 2,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'toilets' => 1,
            'status' => 'active',
            'site_id' => $this->siteA->id,
            'owner_id' => $this->ownerB->id,
        ]);

        // Reviewed Agreements
        foreach ([
            [$this->unitA1, $this->ownerA, 20],
            [$this->unitA2, $this->ownerA, 20],
            [$this->unitB1, $this->ownerB, 15],
        ] as [$u, $o, $rate]) {
            ManagementAgreement::create([
                'unit_id' => $u->id,
                'owner_id' => $o->id,
                'status' => ManagementAgreement::STATUS_REVIEWED,
                'commission_rate' => $rate,
                'deduct_expenses' => true,
                'caution_forfeiture_recipient' => 'company',
                'effective_from' => '2026-01-01',
                'effective_to' => null,
                'reviewed_at' => now(),
            ]);
        }

        $this->postingService = app(LedgerPostingService::class);
        $this->financeService = app(OwnerFinanceQueryService::class);
    }

    private function bookAndComplete(Unit $unit, string $checkIn, string $checkOut, float $subtotal): Reservation
    {
        $guest = User::create([
            'name' => 'Guest '.uniqid(),
            'email' => 'guest'.uniqid().'@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);

        $nights = CarbonImmutable::parse($checkIn)->diffInDays(CarbonImmutable::parse($checkOut));

        $res = Reservation::create([
            'booking_ref' => Reservation::generateBookingRef(),
            'user_id' => $guest->id,
            'property_id' => $unit->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'nights' => $nights,
            'guests' => 2,
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'processing_fee' => 2000,
            'caution_fee' => 20000,
            'tax_amount' => round($subtotal * 0.075, 2),
            'total_amount' => $subtotal + 2000 + 20000 + round($subtotal * 0.075, 2),
            'payment_status' => 'paid',
            'booking_status' => 'checked_out',
            'checked_out_at' => CarbonImmutable::parse($checkOut)->setTime(10, 0)->toDateTimeString(),
        ]);

        $payment = Payment::create([
            'reservation_id' => $res->id,
            'reference' => 'PAY-'.uniqid(),
            'paystack_reference' => 'PST-'.uniqid(),
            'amount' => $res->total_amount,
            'paystack_status' => 'success',
            'payment_method' => 'card',
            'collected_at' => CarbonImmutable::parse($checkIn)->toDateTimeString(),
        ]);

        $this->postingService->postCollection($payment);
        $this->postingService->postStayCompletion($res);

        return $res;
    }

    public function test_ae5_cross_owner_isolation_in_shared_and_multi_property_sites(): void
    {
        // Owner A has stays on A1 (Site A) and A2 (Site B)
        $this->bookAndComplete($this->unitA1, '2026-10-01', '2026-10-05', 200000);
        $this->bookAndComplete($this->unitA2, '2026-10-10', '2026-10-15', 400000);

        // Owner B has stay on B1 (Site A - shared site)
        $this->bookAndComplete($this->unitB1, '2026-10-02', '2026-10-06', 160000);

        // Owner A visits dashboard for October
        $responseA = $this->actingAs($this->ownerUserA)->get(route('owner.dashboard', ['month' => '2026-10']));

        $responseA->assertOk();
        $responseA->assertSee('Alpha Penthouse VI');
        $responseA->assertSee('Alpha Villa Ikoyi');
        $responseA->assertDontSee('Beta Suite VI');

        // Check query service isolation directly
        $dataA = $this->financeService->queryDashboard($this->ownerA, '2026-10');
        $this->assertCount(2, $dataA['properties']);
        $this->assertSame(600000.0, $dataA['totals']['total_earned_revenue']); // 200k + 400k
        $this->assertSame(120000.0, $dataA['totals']['total_commission']); // 20% of 600k
        $this->assertSame(480000.0, $dataA['totals']['total_net_entitlement']);

        // Owner B visits dashboard for October
        $responseB = $this->actingAs($this->ownerUserB)->get(route('owner.dashboard', ['month' => '2026-10']));
        $responseB->assertOk();
        $responseB->assertSee('Beta Suite VI');
        $responseB->assertDontSee('Alpha Penthouse VI');
        $responseB->assertDontSee('Alpha Villa Ikoyi');

        $dataB = $this->financeService->queryDashboard($this->ownerB, '2026-10');
        $this->assertCount(1, $dataB['properties']);
        $this->assertSame(160000.0, $dataB['totals']['total_earned_revenue']);
        $this->assertSame(24000.0, $dataB['totals']['total_commission']); // 15% of 160k
        $this->assertSame(136000.0, $dataB['totals']['total_net_entitlement']);
    }

    public function test_financial_categories_remain_distinct_and_reportable(): void
    {
        $res = $this->bookAndComplete($this->unitA1, '2026-10-01', '2026-10-05', 200000);

        // Add an owner expense
        Expense::create([
            'property_id' => $this->unitA1->id,
            'category' => 'repairs',
            'description' => 'Plumbing fix',
            'amount' => 15000,
            'spent_on' => '2026-10-08',
            'charge_to' => 'owner',
        ]);
        $expense = Expense::first();
        $this->postingService->postExpense($expense);

        $data = $this->financeService->queryDashboard($this->ownerA, '2026-10');

        $this->assertSame(220000.0, $data['totals']['total_collected']); // 200k accom + 20k caution
        $this->assertSame(20000.0, $data['totals']['total_caution_held']); // caution fee liability
        $this->assertSame(200000.0, $data['totals']['total_earned_revenue']);
        $this->assertSame(40000.0, $data['totals']['total_commission']); // 20%
        $this->assertSame(15000.0, $data['totals']['total_expenses']);
        $this->assertSame(145000.0, $data['totals']['total_net_entitlement']); // 200k - 40k - 15k
    }

    public function test_property_and_period_filters_preserve_owner_scope(): void
    {
        // Stays in October and November
        $this->bookAndComplete($this->unitA1, '2026-10-01', '2026-10-05', 200000);
        $this->bookAndComplete($this->unitA1, '2026-11-01', '2026-11-05', 300000);
        $this->bookAndComplete($this->unitA2, '2026-10-10', '2026-10-15', 400000);

        // Filter by property A1 and month October
        $response = $this->actingAs($this->ownerUserA)->get(route('owner.dashboard', [
            'month' => '2026-10',
            'property_id' => $this->unitA1->id,
        ]));

        $response->assertOk();
        $response->assertSee('VI-101');
        $response->assertDontSee('IK-201');

        $data = $this->financeService->queryDashboard($this->ownerA, '2026-10', $this->unitA1->id);
        $this->assertCount(1, $data['properties']);
        $this->assertSame($this->unitA1->id, $data['properties'][0]['property_id']);
        $this->assertSame(200000.0, $data['totals']['total_earned_revenue']);
    }

    public function test_requesting_unowned_property_returns_404_preventing_enumeration(): void
    {
        // Owner A tries to filter with Owner B's property_id
        $this->actingAs($this->ownerUserA)
            ->get(route('owner.dashboard', ['property_id' => $this->unitB1->id]))
            ->assertNotFound();
    }

    public function test_empty_periods_render_clean_zero_state_without_leaking(): void
    {
        // Month with zero activity
        $response = $this->actingAs($this->ownerUserA)->get(route('owner.dashboard', ['month' => '2026-05']));

        $response->assertOk();
        $response->assertSee('0.00');

        $data = $this->financeService->queryDashboard($this->ownerA, '2026-05');
        $this->assertSame(0.0, $data['totals']['total_earned_revenue']);
        $this->assertSame(0.0, $data['totals']['total_net_entitlement']);
    }

    public function test_non_owner_cannot_access_owner_portal(): void
    {
        $customer = User::create([
            'name' => 'Customer User',
            'email' => 'customer@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);
        $customer->assignRole('customer');

        $this->actingAs($customer)
            ->get(route('owner.dashboard'))
            ->assertForbidden();
    }
}
