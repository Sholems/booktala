<?php

namespace Tests\Feature;

use App\Models\BlockedDate;
use App\Models\OtaChannelMapping;
use App\Models\Owner;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\Unit;
use App\Models\User;
use App\Services\Ota\OtaSyncService;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtaChannelSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $guest;

    private Site $site;

    private Owner $owner;

    private Unit $unit;

    private OtaChannelMapping $mapping;

    private OtaSyncService $syncService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->travelTo(CarbonImmutable::parse('2026-11-15 10:00:00'));

        $this->admin = User::create([
            'name' => 'Admin Manager',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);
        $this->admin->assignRole('super_admin');

        $this->guest = User::create([
            'name' => 'Local Guest',
            'email' => 'guest@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);
        $this->guest->assignRole('customer');

        $this->site = Site::create([
            'name' => 'Victoria Island Heights',
            'address' => '10 Adeola Odeku',
            'city' => 'Victoria Island',
            'state' => 'Lagos',
        ]);

        $this->owner = Owner::create([
            'name' => 'Landlord Alpha',
            'type' => 'individual',
            'is_company_owned' => false,
            'is_active' => true,
        ]);

        $this->unit = Unit::create([
            'unit_code' => 'VI-101',
            'title' => 'Alpha Penthouse VI',
            'description' => 'Luxury 2-bed suite',
            'address' => '10 Adeola Odeku',
            'city' => 'Victoria Island',
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

        $this->mapping = OtaChannelMapping::create([
            'property_id' => $this->unit->id,
            'channel' => OtaChannelMapping::CHANNEL_AIRBNB,
            'export_token' => 'test-airbnb-token-12345',
            'import_ical_url' => 'https://www.airbnb.com/calendar/ical/test.ics',
            'rate_multiplier' => 1.15,
            'is_active' => true,
        ]);

        $this->syncService = app(OtaSyncService::class);
    }

    public function test_public_export_feed_serves_valid_ical(): void
    {
        Reservation::create([
            'booking_ref' => 'BK-FEED-1',
            'user_id' => $this->guest->id,
            'property_id' => $this->unit->id,
            'check_in' => '2026-11-20',
            'check_out' => '2026-11-23',
            'nights' => 3,
            'guests' => 2,
            'subtotal' => 300000,
            'discount_amount' => 0,
            'processing_fee' => 0,
            'caution_fee' => 0,
            'tax_amount' => 0,
            'total_amount' => 300000,
            'booking_status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        BlockedDate::create([
            'property_id' => $this->unit->id,
            'start_date' => '2026-11-25',
            'end_date' => '2026-11-27',
            'reason' => 'AC Maintenance',
        ]);

        $response = $this->get(route('ota.feed', ['token' => $this->mapping->export_token]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/calendar; charset=utf-8');

        $content = $response->getContent();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $content);
        $this->assertStringContainsString('DTSTART;VALUE=DATE:20261120', $content);
        $this->assertStringContainsString('DTEND;VALUE=DATE:20261123', $content);
        $this->assertStringContainsString('Reserved - BookTala', $content);
        $this->assertStringContainsString('Blocked - Maintenance', $content);
        $this->assertStringContainsString('END:VCALENDAR', $content);
    }

    public function test_inbound_sync_imports_new_reservation(): void
    {
        $sampleIcal = <<<'ICAL'
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Airbnb Inc//Hosting Calendar 0.8.8//EN
CALSCALE:GREGORIAN
BEGIN:VEVENT
DTEND;VALUE=DATE:20261205
DTSTART;VALUE=DATE:20261201
UID:airbnb-reservation-998877@airbnb.com
DESCRIPTION:Reservation URL: https://www.airbnb.com/reservation/itinerary?code=HM998877
SUMMARY:Reserved (John Doe)
END:VEVENT
END:VCALENDAR
ICAL;

        $results = $this->syncService->syncMapping($this->mapping, $sampleIcal);

        $this->assertEquals(1, $results['imported']);
        $this->assertEquals(0, $results['conflicts']);

        $imported = Reservation::where('paystack_reference', 'OTA-airbnb-reservation-998877@airbnb.com')->first();
        $this->assertNotNull($imported);
        $this->assertEquals('2026-12-01', $imported->check_in->format('Y-m-d'));
        $this->assertEquals('2026-12-05', $imported->check_out->format('Y-m-d'));
        $this->assertEquals('airbnb', $imported->booking_source);
        $this->assertEquals('confirmed', $imported->booking_status);
        $this->assertEquals(460000, $imported->total_amount);
    }

    public function test_inbound_sync_prevents_double_booking_collision(): void
    {
        Reservation::create([
            'booking_ref' => 'BK-EXISTING',
            'user_id' => $this->guest->id,
            'property_id' => $this->unit->id,
            'check_in' => '2026-12-01',
            'check_out' => '2026-12-05',
            'nights' => 4,
            'guests' => 2,
            'subtotal' => 400000,
            'discount_amount' => 0,
            'processing_fee' => 0,
            'caution_fee' => 0,
            'tax_amount' => 0,
            'total_amount' => 400000,
            'booking_status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        $conflictingIcal = <<<'ICAL'
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Airbnb Inc//Hosting Calendar 0.8.8//EN
CALSCALE:GREGORIAN
BEGIN:VEVENT
DTEND;VALUE=DATE:20261207
DTSTART;VALUE=DATE:20261203
UID:airbnb-collision-112233@airbnb.com
SUMMARY:Reserved (Jane Smith)
END:VEVENT
END:VCALENDAR
ICAL;

        $results = $this->syncService->syncMapping($this->mapping, $conflictingIcal);

        $this->assertEquals(0, $results['imported']);
        $this->assertEquals(1, $results['conflicts']);

        $mapping = $this->mapping->fresh();
        $this->assertEquals('conflict', $mapping->sync_status);
        $this->assertNotNull($mapping->conflict_details);
        $this->assertEquals('airbnb-collision-112233@airbnb.com', $mapping->conflict_details[0]['uid']);

        $this->assertNull(Reservation::where('paystack_reference', 'OTA-airbnb-collision-112233@airbnb.com')->first());
    }

    public function test_admin_can_manage_channel_mappings(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.channel-manager.mapping.store'), [
            'property_id' => $this->unit->id,
            'channel' => 'bookingcom',
            'import_ical_url' => 'https://admin.booking.com/hotel/hoteladmin/ical.html?t=123',
            'rate_multiplier' => 1.20,
            'min_stay_override' => 2,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ota_channel_mappings', [
            'property_id' => $this->unit->id,
            'channel' => 'bookingcom',
            'rate_multiplier' => 1.20,
            'min_stay_override' => 2,
        ]);
    }

    public function test_ota_sync_artisan_command_executes_successfully(): void
    {
        $this->artisan('ota:sync', ['--channel' => 'airbnb'])
            ->expectsOutputToContain('Starting Two-Way OTA Synchronization...')
            ->assertSuccessful();
    }

    public function test_rate_multiplier_applies_markup_to_inbound_ota_reservations(): void
    {
        // Unit base rate = 100,000 per night.
        // Mapping rate_multiplier = 1.15 (+15%).
        // 2 nights = 200,000 * 1.15 = 230,000.
        $sampleIcal = <<<'ICAL'
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Airbnb Inc//Hosting Calendar 0.8.8//EN
BEGIN:VEVENT
DTEND;VALUE=DATE:20261220
DTSTART;VALUE=DATE:20261218
UID:airbnb-markup-test-7788@airbnb.com
SUMMARY:Reserved (Alice Walker)
END:VEVENT
END:VCALENDAR
ICAL;

        $results = $this->syncService->syncMapping($this->mapping, $sampleIcal);
        $this->assertEquals(1, $results['imported']);

        $reservation = Reservation::where('paystack_reference', 'OTA-airbnb-markup-test-7788@airbnb.com')->first();
        $this->assertNotNull($reservation);
        $this->assertEquals(230000, (int) $reservation->total_amount);
        $this->assertEquals('airbnb', $reservation->booking_source);
    }

    public function test_ota_feed_excludes_cancelled_reservations(): void
    {
        // Active reservation - should be in feed
        Reservation::create([
            'booking_ref' => 'BK-ACTIVE-1',
            'user_id' => $this->guest->id,
            'property_id' => $this->unit->id,
            'check_in' => '2026-11-20',
            'check_out' => '2026-11-22',
            'nights' => 2,
            'guests' => 2,
            'subtotal' => 200000,
            'total_amount' => 200000,
            'booking_status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        // Cancelled reservation - MUST NOT be in feed (avoids false calendar blocks)
        Reservation::create([
            'booking_ref' => 'BK-CANCELLED-1',
            'user_id' => $this->guest->id,
            'property_id' => $this->unit->id,
            'check_in' => '2026-11-25',
            'check_out' => '2026-11-28',
            'nights' => 3,
            'guests' => 2,
            'subtotal' => 300000,
            'total_amount' => 300000,
            'booking_status' => 'cancelled',
            'payment_status' => 'pending',
        ]);

        $response = $this->get(route('ota.feed', ['token' => $this->mapping->export_token]));
        $response->assertOk();

        $content = $response->getContent();
        $this->assertStringContainsString('DTSTART;VALUE=DATE:20261120', $content);
        $this->assertStringNotContainsString('DTSTART;VALUE=DATE:20261125', $content);
    }
}
