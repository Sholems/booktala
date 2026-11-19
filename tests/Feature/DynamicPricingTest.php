<?php

namespace Tests\Feature;

use App\Models\Owner;
use App\Models\PricingRule;
use App\Models\Site;
use App\Models\Unit;
use App\Models\User;
use App\Services\PricingService;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicPricingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Site $site;

    private Owner $owner;

    private Unit $unit;

    private PricingService $pricingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->travelTo(CarbonImmutable::parse('2026-11-15 10:00:00'));

        $this->admin = User::create([
            'name' => 'Pricing Admin',
            'email' => 'pricing@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);
        $this->admin->assignRole('super_admin');

        $this->site = Site::create([
            'name' => 'Ikoyi Luxury Terraces',
            'address' => '5 Alexander Road',
            'city' => 'Ikoyi',
            'state' => 'Lagos',
        ]);

        $this->owner = Owner::create([
            'name' => 'Ikoyi Landlord',
            'type' => 'individual',
            'is_company_owned' => false,
            'is_active' => true,
        ]);

        $this->unit = Unit::create([
            'unit_code' => 'IK-101',
            'title' => 'Ikoyi Presidential Villa',
            'description' => 'Luxury residence',
            'address' => '5 Alexander Road',
            'city' => 'Ikoyi',
            'state' => 'Lagos',
            'price_per_night' => 100000,
            'weekend_price' => 120000,
            'price_per_week' => 600000, // 600k weekly (discount vs 700k)
            'min_nights' => 1,
            'max_nights' => 60,
            'max_guests' => 6,
            'bedrooms' => 3,
            'bathrooms' => 3,
            'toilets' => 3,
            'status' => 'active',
            'site_id' => $this->site->id,
            'owner_id' => $this->owner->id,
        ]);

        $this->pricingService = app(PricingService::class);
    }

    public function test_pricing_calculates_standard_weekday_and_weekend_rates(): void
    {
        // Monday Nov 16 to Wednesday Nov 18 = 2 weekday nights (2 * 100,000 = 200,000)
        $quoteWeekday = $this->pricingService->calculatePrice($this->unit, '2026-11-16', '2026-11-18');
        $this->assertEquals(2, $quoteWeekday['nights']);
        $this->assertEquals(200000, $quoteWeekday['subtotal']);

        // Friday Nov 20 to Sunday Nov 22 = 2 weekend nights (2 * 120,000 = 240,000)
        $quoteWeekend = $this->pricingService->calculatePrice($this->unit, '2026-11-20', '2026-11-22');
        $this->assertEquals(2, $quoteWeekend['nights']);
        $this->assertEquals(240000, $quoteWeekend['subtotal']);
    }

    public function test_seasonal_percentage_surge_adjusts_nightly_price(): void
    {
        // Add December Festive Surge: +30% markup from Dec 20 to Dec 31
        PricingRule::create([
            'property_id' => $this->unit->id,
            'name' => 'December Festive Surge',
            'type' => 'seasonal',
            'start_date' => '2026-12-20',
            'end_date' => '2026-12-31',
            'adjustment_type' => 'percentage_markup',
            'adjustment_value' => 30, // +30%
            'priority' => 10,
            'is_active' => true,
        ]);

        // Dec 21 to Dec 23 = 2 weekday nights (each 100,000 * 1.30 = 130,000) => 260,000
        $quote = $this->pricingService->calculatePrice($this->unit, '2026-12-21', '2026-12-23');
        $this->assertEquals(2, $quote['nights']);
        $this->assertEquals(260000, $quote['subtotal']);
        $this->assertContains('December Festive Surge', $quote['applied_rules']);
    }

    public function test_minimum_stay_rule_is_enforced(): void
    {
        // Rule: Christmas & New Year requires minimum 4 nights stay
        PricingRule::create([
            'property_id' => $this->unit->id,
            'name' => 'Christmas Min Stay',
            'type' => 'event',
            'start_date' => '2026-12-24',
            'end_date' => '2026-12-28',
            'adjustment_type' => 'percentage_markup',
            'adjustment_value' => 20,
            'min_nights' => 4,
            'priority' => 15,
            'is_active' => true,
        ]);

        // Attempting to book 2 nights inside this window must throw DomainException
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("Minimum stay for dates covered by 'Christmas Min Stay' is 4 nights.");

        $this->pricingService->calculatePrice($this->unit, '2026-12-24', '2026-12-26');
    }

    public function test_length_of_stay_weekly_discount_is_applied(): void
    {
        // 7 nights (Nov 16 to Nov 23)
        // Without weekly override: 5 weekdays @ 100k (500k) + 2 weekends @ 120k (240k) = 740,000
        // With weekly price: capped at 600,000
        $quote = $this->pricingService->calculatePrice($this->unit, '2026-11-16', '2026-11-23');

        $this->assertEquals(7, $quote['nights']);
        $this->assertEquals(600000, $quote['subtotal']);
        $this->assertGreaterThan(0, $quote['length_of_stay_discount']);
    }

    public function test_admin_can_manage_pricing_rules(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.pricing-rules.store'), [
            'name' => 'Easter Special',
            'type' => 'event',
            'property_id' => $this->unit->id,
            'start_date' => '2027-04-01',
            'end_date' => '2027-04-05',
            'adjustment_type' => 'percentage_markup',
            'adjustment_value' => 15,
            'min_nights' => 3,
            'priority' => 20,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.pricing-rules.index'));
        $this->assertDatabaseHas('pricing_rules', [
            'name' => 'Easter Special',
            'property_id' => $this->unit->id,
            'adjustment_type' => 'percentage_markup',
            'adjustment_value' => 15,
            'min_nights' => 3,
        ]);
    }

    public function test_pricing_simulator_renders_daily_breakdown(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.pricing-rules.simulator', [
            'unit_id' => $this->unit->id,
            'month' => '2026-12',
        ]));

        $response->assertOk();
        $response->assertSee('Dynamic Rate Simulator');
        $response->assertSee('Ikoyi Presidential Villa');
        $response->assertSee('December 2026');
    }

    public function test_regular_admin_role_can_view_and_manage_pricing_rules(): void
    {
        $regularAdmin = User::create([
            'name' => 'Regular Admin Staff',
            'email' => 'regularadmin@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);
        $regularAdmin->assignRole('admin');

        // Test regular admin can view index
        $indexResponse = $this->actingAs($regularAdmin)->get(route('admin.pricing-rules.index'));
        $indexResponse->assertOk();

        // Test regular admin can view simulator
        $simResponse = $this->actingAs($regularAdmin)->get(route('admin.pricing-rules.simulator', [
            'unit_id' => $this->unit->id,
            'month' => '2026-12',
        ]));
        $simResponse->assertOk();

        // Test regular admin can create pricing rule
        $createResponse = $this->actingAs($regularAdmin)->post(route('admin.pricing-rules.store'), [
            'name' => 'Regular Admin Promo',
            'type' => 'promo',
            'property_id' => $this->unit->id,
            'start_date' => '2027-01-10',
            'end_date' => '2027-01-20',
            'adjustment_type' => 'percentage_discount',
            'adjustment_value' => 10,
            'is_active' => 1,
        ]);
        $createResponse->assertRedirect(route('admin.pricing-rules.index'));
        $this->assertDatabaseHas('pricing_rules', [
            'name' => 'Regular Admin Promo',
            'adjustment_value' => 10,
        ]);
    }
}
