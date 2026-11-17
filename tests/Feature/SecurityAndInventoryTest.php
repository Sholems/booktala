<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\InventoryItem;
use App\Models\PatrolCheckpoint;
use App\Models\PatrolScan;
use App\Models\Position;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\Staff;
use App\Models\User;
use App\Models\VisitorLog;
use App\Notifications\OperationsAlert;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 4: visitor log, incidents, QR patrols, site inventory and the site report.
 * Lekki has a guard, a supervisor and a manager; Abuja has its own guard.
 */
class SecurityAndInventoryTest extends TestCase
{
    use RefreshDatabase;

    private Site $lekki;
    private Site $abuja;
    private Property $lekkiFlat;
    private User $admin;
    private User $guard;
    private User $supervisor;
    private User $manager;
    private User $abujaGuard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->travelTo(CarbonImmutable::parse('2026-11-16 18:00'));

        $this->lekki = Site::create(['name' => 'Lekki Gardens', 'address' => '1 Way', 'city' => 'Lekki', 'state' => 'Lagos']);
        $this->abuja = Site::create(['name' => 'Maitama Court', 'address' => '5 Gana', 'city' => 'Abuja', 'state' => 'FCT']);
        $this->lekkiFlat = $this->makeProperty('Lekki Flat', $this->lekki);
        $this->makeProperty('Abuja Flat', $this->abuja);

        $this->admin = $this->makeUser('admin');
        $this->guard = $this->makeStaff('Security Guard', $this->lekki);
        $this->supervisor = $this->makeStaff('Head of Security', $this->lekki);
        $this->manager = $this->makeStaff('Operations Manager', $this->lekki);
        $this->abujaGuard = $this->makeStaff('Security Guard', $this->abuja);
    }

    // --- Helpers ---------------------------------------------------------------------------

    private function makeUser(string $role): User
    {
        $user = User::create(['name' => ucfirst($role) . ' ' . uniqid(), 'email' => $role . uniqid() . '@example.com', 'password' => 'password123', 'status' => 'active']);
        $user->assignRole($role);

        return $user;
    }

    private function makeStaff(string $position, Site $site): User
    {
        $user = $this->makeUser('staff');
        $staff = Staff::create(['user_id' => $user->id, 'employee_id' => 'T-' . $user->id, 'hire_date' => '2026-01-01']);
        $staff->assignTo($site, Position::where('name', $position)->firstOrFail(), true, '2026-01-01');

        return $user->fresh();
    }

    private function makeProperty(string $title, Site $site): Property
    {
        return Property::create([
            'title' => $title, 'description' => 'Nice', 'address' => '1 Street', 'city' => 'Lagos', 'state' => 'Lagos',
            'price_per_night' => 10000, 'max_guests' => 2, 'bedrooms' => 1, 'bathrooms' => 1, 'toilets' => 1, 'status' => 'active',
            'site_id' => $site->id,
        ]);
    }

    private function alerts(User $user): \Illuminate\Support\Collection
    {
        return $user->notifications()->get()->where('type', OperationsAlert::class);
    }

    private function incident(User $by, string $severity, array $extra = [])
    {
        return $this->actingAs($by)->post(route('staff.security.incidents.store'), array_merge([
            'site_id' => $this->lekki->id, 'occurred_at' => now()->subMinutes(10)->format('Y-m-d H:i'),
            'category' => 'theft', 'severity' => $severity, 'title' => 'Generator battery stolen', 'description' => 'Found missing on rounds.',
        ], $extra));
    }

    // --- Visitors ------------------------------------------------------------------------------

    public function test_gate_signs_visitors_in_and_out_at_their_own_site_only(): void
    {
        $this->actingAs($this->guard)->post(route('staff.security.visitors.store'), [
            'site_id' => $this->lekki->id, 'visitor_name' => 'Tunde Bakare', 'purpose' => 'delivery',
            'property_id' => $this->lekkiFlat->id, 'vehicle_plate' => 'LSD-123-XY', 'id_type' => 'nin',
        ])->assertSessionHas('success');
        $visitor = VisitorLog::firstOrFail();

        $this->actingAs($this->guard)->get(route('staff.security.index'))->assertOk()->assertSee('Tunde Bakare')->assertSee('LSD-123-XY');
        $this->actingAs($this->abujaGuard)->get(route('staff.security.index'))->assertOk()->assertDontSee('Tunde Bakare');
        $this->actingAs($this->abujaGuard)->patch(route('staff.security.visitors.out', $visitor))->assertNotFound();
        $this->actingAs($this->abujaGuard)->post(route('staff.security.visitors.store'), ['site_id' => $this->lekki->id, 'visitor_name' => 'X', 'purpose' => 'other'])->assertNotFound();
        $this->actingAs($this->abujaGuard)->get(route('staff.security.index', ['site' => $this->lekki->id]))->assertNotFound();

        $abujaFlat = Property::where('site_id', $this->abuja->id)->firstOrFail();
        $this->actingAs($this->guard)->post(route('staff.security.visitors.store'), [
            'site_id' => $this->lekki->id, 'visitor_name' => 'Y', 'purpose' => 'other', 'property_id' => $abujaFlat->id,
        ])->assertSessionHasErrors('property_id');

        $this->actingAs($this->guard)->patch(route('staff.security.visitors.out', $visitor))->assertSessionHas('success');
        $this->assertNotNull($visitor->fresh()->left_at);
        $this->assertSame($this->guard->id, $visitor->fresh()->signed_out_by);
    }

    // --- Incidents -----------------------------------------------------------------------------

    public function test_incident_alerts_depend_on_severity(): void
    {
        $this->incident($this->guard, 'medium')->assertRedirect();
        $this->assertCount(0, $this->alerts($this->supervisor));

        $this->incident($this->guard, 'high');
        $this->assertCount(1, $this->alerts($this->supervisor));
        $this->assertCount(0, $this->alerts($this->manager));
        $this->assertCount(0, $this->alerts($this->admin));

        $this->incident($this->guard, 'critical', ['category' => 'fire', 'title' => 'Fire in bin store']);
        $this->assertCount(2, $this->alerts($this->supervisor));
        $this->assertCount(1, $this->alerts($this->manager));
        $this->assertCount(1, $this->alerts($this->admin));
        $this->assertCount(0, $this->alerts($this->guard), 'The reporter is not alerted about their own report');
        $this->assertCount(0, $this->alerts($this->abujaGuard));
        $this->assertStringContainsString('Critical incident at Lekki Gardens', $this->alerts($this->admin)->first()->data['title']);
    }

    public function test_incident_photo_is_private_and_only_supervisors_resolve(): void
    {
        Storage::fake('local');
        $this->incident($this->guard, 'medium', ['photo' => UploadedFile::fake()->image('door.jpg')]);
        $incident = Incident::firstOrFail();
        Storage::disk('local')->assertExists($incident->photo_path);

        $this->actingAs($this->guard)->get(route('staff.security.incidents.photo', $incident))->assertOk();
        $this->actingAs($this->abujaGuard)->get(route('staff.security.incidents.show', $incident))->assertNotFound();
        $this->actingAs($this->abujaGuard)->get(route('staff.security.incidents.photo', $incident))->assertNotFound();
        $this->actingAs($this->admin)->get(route('admin.security.incidents.show', $incident))->assertOk()->assertSee('Generator battery stolen');

        $this->actingAs($this->guard)->patch(route('staff.security.incidents.resolve', $incident), ['resolution' => 'x'])->assertForbidden();
        $this->actingAs($this->supervisor)->patch(route('staff.security.incidents.resolve', $incident), ['resolution' => 'Police report LK/2211; battery replaced'])->assertSessionHas('success');
        $this->assertSame('resolved', $incident->fresh()->status);
        $this->assertSame($this->supervisor->id, $incident->fresh()->resolved_by);
    }

    // --- Patrols --------------------------------------------------------------------------------

    public function test_guard_records_a_patrol_by_scanning_the_checkpoint_code(): void
    {
        $this->actingAs($this->supervisor)->post(route('staff.security.checkpoints.store'), ['site_id' => $this->lekki->id, 'name' => 'Back gate'])->assertSessionHas('success');
        $this->actingAs($this->guard)->post(route('staff.security.checkpoints.store'), ['site_id' => $this->lekki->id, 'name' => 'Pool'])->assertForbidden();
        $checkpoint = PatrolCheckpoint::firstOrFail();

        $this->actingAs($this->supervisor)->get(route('staff.security.checkpoints.print', ['site' => $this->lekki->id]))
            ->assertOk()->assertSee('Back gate')->assertSee($checkpoint->scanUrl());
        $this->actingAs($this->guard)->get(route('staff.security.checkpoints.print', ['site' => $this->lekki->id]))->assertForbidden();

        $this->actingAs($this->guard)->get($checkpoint->scanUrl())->assertOk()->assertSee('Back gate')->assertSee('Record patrol');
        $this->actingAs($this->guard)->post(route('staff.patrol.record', $checkpoint->token), ['lat' => 6.4474, 'lng' => 3.4723])->assertSessionHas('success');
        $this->actingAs($this->guard)->post(route('staff.patrol.record', $checkpoint->token))->assertSessionHas('success');
        $this->assertSame(1, PatrolScan::count(), 'A double tap counts once');

        $this->actingAs($this->abujaGuard)->get($checkpoint->scanUrl())->assertNotFound();
        $this->actingAs($this->supervisor)->patch(route('staff.security.checkpoints.remove', $checkpoint));
        $this->actingAs($this->guard)->get($checkpoint->scanUrl())->assertNotFound();
    }

    public function test_missed_patrols_alert_the_supervisor_once_per_gap_within_patrol_hours(): void
    {
        $this->lekki->update(['patrol_interval_minutes' => 60, 'patrol_from' => '20:00', 'patrol_until' => '06:00']);
        $gate = PatrolCheckpoint::create(['site_id' => $this->lekki->id, 'name' => 'Main gate']);

        $this->artisan('operations:check'); // 18:00, outside patrol hours
        $this->travelTo(CarbonImmutable::parse('2026-11-16 21:59'));
        $this->artisan('operations:check'); // patrols began 20:00: not yet 2 x 60 min
        $this->assertCount(0, $this->alerts($this->supervisor));

        $this->travelTo(CarbonImmutable::parse('2026-11-16 22:01'));
        $this->artisan('operations:check');
        $this->artisan('operations:check');
        $this->assertCount(1, $this->alerts($this->supervisor));
        $this->assertStringContainsString('Patrol missed: Main gate', $this->alerts($this->supervisor)->first()->data['title']);

        // A scan re-arms it; the next gap alerts again.
        PatrolScan::create(['patrol_checkpoint_id' => $gate->id, 'staff_id' => $this->guard->staff->id, 'scanned_at' => now()]);
        $this->travelTo(CarbonImmutable::parse('2026-11-17 00:00'));
        $this->artisan('operations:check');
        $this->assertCount(1, $this->alerts($this->supervisor));
        $this->travelTo(CarbonImmutable::parse('2026-11-17 00:05'));
        $this->artisan('operations:check');
        $this->assertCount(2, $this->alerts($this->supervisor));

        // Daytime is outside patrol hours.
        $this->travelTo(CarbonImmutable::parse('2026-11-17 13:00'));
        $this->artisan('operations:check');
        $this->assertCount(2, $this->alerts($this->supervisor));
    }

    // --- Inventory -------------------------------------------------------------------------------

    public function test_stock_use_laundry_and_low_stock_alert(): void
    {
        $this->actingAs($this->supervisor)->post(route('staff.inventory.store'), [
            'site_id' => $this->lekki->id, 'name' => 'Bath towel', 'category' => 'linen', 'unit' => 'pcs',
            'opening_stock' => 20, 'reorder_level' => 5, 'tracks_laundry' => 1,
        ])->assertSessionHas('success');
        $towel = InventoryItem::where('name', 'Bath towel')->firstOrFail();
        $this->assertEquals(20, $towel->on_hand);

        $record = fn (User $u, string $type, $qty) => $this->actingAs($u)->post(route('staff.inventory.movements.store', $towel), ['type' => $type, 'quantity' => $qty]);

        $record($this->guard, 'to_laundry', 8)->assertSessionHas('success');
        $record($this->guard, 'from_laundry', 3)->assertSessionHas('success');
        $towel->refresh();
        $this->assertEquals(15, $towel->on_hand);
        $this->assertEquals(5, $towel->at_laundry);

        $record($this->guard, 'from_laundry', 6)->assertSessionHasErrors('quantity');
        $record($this->guard, 'used', 99)->assertSessionHasErrors('quantity');
        $record($this->guard, 'received', 10)->assertForbidden();

        $record($this->guard, 'used', 10);
        $this->assertCount(1, $this->alerts($this->supervisor), 'Down to 5 = reorder level');
        $record($this->guard, 'discarded', 1);
        $this->assertCount(1, $this->alerts($this->supervisor), 'Only once until restocked');

        $record($this->supervisor, 'received', 30)->assertSessionHas('success');
        $this->assertEquals(34, $towel->fresh()->on_hand);
        $this->assertNull($towel->fresh()->low_stock_alerted_at);
        $record($this->supervisor, 'count', 4);
        $this->assertCount(2, $this->alerts($this->supervisor));

        $this->actingAs($this->abujaGuard)->post(route('staff.inventory.movements.store', $towel), ['type' => 'used', 'quantity' => 1])->assertNotFound();
        $this->actingAs($this->guard)->get(route('staff.inventory.show', $towel))->assertOk()->assertSee('Sent to laundry');
        $this->actingAs($this->abujaGuard)->get(route('staff.inventory.index'))->assertOk()->assertDontSee('Bath towel');
    }

    public function test_laundry_moves_are_refused_for_items_not_tracked_through_laundry(): void
    {
        $soap = InventoryItem::create(['site_id' => $this->lekki->id, 'name' => 'Soap', 'category' => 'toiletries', 'unit' => 'bars', 'on_hand' => 10]);

        $this->actingAs($this->guard)->post(route('staff.inventory.movements.store', $soap), ['type' => 'to_laundry', 'quantity' => 1])->assertSessionHasErrors('type');
    }

    // --- Head office -----------------------------------------------------------------------------

    public function test_site_report_compares_sites_and_exports(): void
    {
        $guest = $this->makeUser('customer');
        Reservation::create([
            'booking_ref' => Reservation::generateBookingRef(), 'user_id' => $guest->id, 'property_id' => $this->lekkiFlat->id,
            'check_in' => '2026-11-03', 'check_out' => '2026-11-08', 'nights' => 5, 'guests' => 2,
            'subtotal' => 50000, 'discount_amount' => 0, 'tax_amount' => 0, 'total_amount' => 50000,
            'payment_status' => 'paid', 'booking_status' => 'checked_out',
        ]);
        $this->incident($this->guard, 'high', ['occurred_at' => '2026-11-05 10:00']);
        $this->incident($this->guard, 'high', ['occurred_at' => '2026-11-12 10:00']); // after the period
        VisitorLog::create(['site_id' => $this->lekki->id, 'visitor_name' => 'V', 'purpose' => 'delivery', 'arrived_at' => '2026-11-05 09:00']);
        InventoryItem::create(['site_id' => $this->lekki->id, 'name' => 'Bleach', 'category' => 'cleaning', 'unit' => 'L', 'on_hand' => 1, 'reorder_level' => 2]);

        $range = ['from' => '2026-11-01', 'to' => '2026-11-10'];
        $this->actingAs($this->admin)->get(route('admin.reports.sites', $range))
            ->assertOk()->assertSee('Lekki Gardens')->assertSee('Maitama Court')->assertSee('50%')->assertSee('₦50,000');

        $csv = $this->actingAs($this->admin)->get(route('admin.reports.sites.export', $range))->assertOk()->streamedContent();
        $lines = array_map('str_getcsv', array_filter(explode("\n", trim($csv))));
        $header = $lines[0];
        $lekki = collect($lines)->first(fn ($l) => $l[0] === 'Lekki Gardens');
        $row = array_combine($header, $lekki);
        $this->assertEquals(50, $row['Occupancy %']);
        $this->assertEquals(50000, $row['Room revenue']);
        $this->assertEquals(1, $row['High/critical incidents']);
        $this->assertEquals(1, $row['Visitors']);
        $this->assertEquals(1, $row['Low-stock items (now)']);

        $this->actingAs($this->guard)->get(route('admin.reports.sites'))->assertForbidden();
    }

    public function test_team_overview_and_admin_pages_show_security_and_stock(): void
    {
        $this->incident($this->guard, 'medium');
        VisitorLog::create(['site_id' => $this->lekki->id, 'visitor_name' => 'V', 'purpose' => 'delivery', 'arrived_at' => now()]);

        $this->actingAs($this->supervisor)->get(route('staff.team.index'))->assertOk()->assertSee('Open incidents')->assertSee('Visitors on site');
        $this->actingAs($this->admin)->get(route('admin.security.index', ['site' => $this->lekki->id]))->assertOk()->assertSee('Generator battery stolen');
        $this->actingAs($this->admin)->get(route('admin.inventory.index', ['site' => $this->abuja->id]))->assertOk()->assertSee('Maitama Court');
        $this->actingAs($this->guard)->get(route('admin.security.index'))->assertForbidden();
    }
}
