<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\MaintenanceReport;
use App\Models\Position;
use App\Models\Property;
use App\Models\Site;
use App\Models\Staff;
use App\Models\StaffSchedule;
use App\Models\Task;
use App\Models\User;
use App\Notifications\OperationsAlert;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Phase 2: rosters per site, clock in/out with location, automatic routing,
 * escalation of unstarted work, missed-shift alerts and supervisor tools.
 *
 * Lekki has coordinates (6.4474, 3.4723) and a 200 m radius; Abuja is a second site.
 */
class SiteOperationsTest extends TestCase
{
    use RefreshDatabase;

    private Site $lekki;
    private Site $abuja;
    private Property $lekkiFlat;
    private Property $abujaFlat;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->travelTo(today()->setTime(8, 5));

        $this->lekki = Site::create(['name' => 'Lekki Gardens', 'address' => '1 Admiralty Way', 'city' => 'Lekki', 'state' => 'Lagos', 'latitude' => 6.4474, 'longitude' => 3.4723, 'geofence_radius_m' => 200]);
        $this->abuja = Site::create(['name' => 'Maitama Court', 'address' => '5 Gana Street', 'city' => 'Abuja', 'state' => 'FCT']);
        $this->lekkiFlat = $this->makeProperty('Lekki Flat', $this->lekki);
        $this->abujaFlat = $this->makeProperty('Abuja Flat', $this->abuja);
        $this->admin = $this->makeUser('admin');
    }

    // --- Helpers -------------------------------------------------------------------

    private function makeUser(string $role): User
    {
        $user = User::create(['name' => ucfirst($role) . ' ' . uniqid(), 'email' => $role . uniqid() . '@example.com', 'password' => 'password123', 'status' => 'active']);
        $user->assignRole($role);

        return $user;
    }

    private function makeStaff(string $position, Site $site): User
    {
        $user = $this->makeUser('staff');
        $staff = Staff::create(['user_id' => $user->id, 'employee_id' => 'T-' . $user->id, 'hire_date' => today()]);
        $staff->assignTo($site, Position::where('name', $position)->firstOrFail());

        return $user->fresh();
    }

    private function makeProperty(string $title, Site $site): Property
    {
        return Property::create([
            'title' => $title . ' ' . uniqid(), 'description' => 'Nice', 'address' => '1 Street', 'city' => 'Lagos', 'state' => 'Lagos',
            'price_per_night' => 10000, 'max_guests' => 2, 'bedrooms' => 1, 'bathrooms' => 1, 'toilets' => 1, 'status' => 'active',
            'site_id' => $site->id,
        ]);
    }

    private function shift(User $user, Site $site, string $type = 'day', $date = null): StaffSchedule
    {
        [$start, $end] = StaffSchedule::SHIFT_TIMES[$type];

        return StaffSchedule::create([
            'staff_id' => $user->staff->id, 'site_id' => $site->id, 'date' => ($date ?? today())->toDateString(),
            'shift_type' => $type, 'start_time' => $start, 'end_time' => $end, 'status' => 'scheduled',
        ]);
    }

    private function alertsFor(User $user): \Illuminate\Support\Collection
    {
        return $user->notifications()->get()->where('type', OperationsAlert::class);
    }

    // --- Clock in / out ------------------------------------------------------------------

    public function test_clock_in_on_site_is_linked_to_the_shift(): void
    {
        $desk = $this->makeStaff('Receptionist', $this->lekki);
        $shift = $this->shift($desk, $this->lekki); // 08:00, now 08:05

        $this->actingAs($desk)->post(route('staff.attendance.clock-in'), [
            'site_id' => $this->lekki->id, 'lat' => 6.4475, 'lng' => 3.4724, 'accuracy' => 15,
        ])->assertSessionHas('success');

        $record = Attendance::firstOrFail();
        $this->assertSame($shift->id, $record->staff_schedule_id);
        $this->assertSame('on_site', $record->clock_in_location);
        $this->assertSame(5, $record->late_minutes);
        $this->assertFalse($record->isLate(), 'Within the grace period');
        $this->assertSame([], $record->flags());
    }

    public function test_late_and_off_site_clock_in_is_recorded_and_flagged_not_blocked(): void
    {
        $desk = $this->makeStaff('Receptionist', $this->lekki);
        $this->shift($desk, $this->lekki);
        $this->travelTo(today()->setTime(8, 40));

        $this->actingAs($desk)->post(route('staff.attendance.clock-in'), [
            'site_id' => $this->lekki->id, 'lat' => 6.5244, 'lng' => 3.3792, 'accuracy' => 20, // Ikeja, ~13 km away
        ])->assertSessionHas('success');

        $record = Attendance::firstOrFail();
        $this->assertSame('off_site', $record->clock_in_location);
        $this->assertGreaterThan(10000, $record->clock_in_distance_m);
        $this->assertTrue($record->isLate());
        $this->assertContains('Late 40 min', $record->flags());
    }

    public function test_cannot_clock_in_twice_or_at_another_site(): void
    {
        $desk = $this->makeStaff('Receptionist', $this->lekki);

        $this->actingAs($desk)->post(route('staff.attendance.clock-in'), ['site_id' => $this->abuja->id])->assertSessionHasErrors('site_id');
        $this->actingAs($desk)->post(route('staff.attendance.clock-in'), ['site_id' => $this->lekki->id])->assertSessionHas('success');
        $this->actingAs($desk)->post(route('staff.attendance.clock-in'), ['site_id' => $this->lekki->id])->assertSessionHasErrors('site_id');

        $this->assertSame(1, Attendance::count());
        $this->assertSame('unknown', Attendance::first()->clock_in_location, 'No location shared');
    }

    public function test_clock_out_records_hours_and_handover_seen_by_next_shift(): void
    {
        $night = $this->makeStaff('Security Guard', $this->lekki);
        $day = $this->makeStaff('Security Guard', $this->lekki);
        $shift = $this->shift($night, $this->lekki);

        $this->actingAs($night)->post(route('staff.attendance.clock-in'), ['site_id' => $this->lekki->id]);
        $this->travelTo(now()->addHours(8)->addMinutes(30));
        $this->actingAs($night)->post(route('staff.attendance.clock-out'), ['handover_note' => 'Gate light at block C is faulty'])->assertSessionHas('success');

        $record = Attendance::firstOrFail();
        $this->assertSame(510, $record->worked_minutes);
        $this->assertSame('completed', $shift->fresh()->status);

        $this->actingAs($day)->get(route('staff.dashboard'))->assertOk()->assertSee('Gate light at block C is faulty');
        $this->actingAs($night)->post(route('staff.attendance.clock-out'))->assertSessionHasErrors('clock');
    }

    public function test_overnight_shift_clock_in_after_midnight_links_to_last_nights_shift(): void
    {
        $guard = $this->makeStaff('Security Guard', $this->lekki);
        $shift = $this->shift($guard, $this->lekki, 'night', today()->subDay()); // 22:00 yesterday – 06:00 today
        $this->travelTo(today()->setTime(1, 0));

        $this->actingAs($guard)->post(route('staff.attendance.clock-in'), ['site_id' => $this->lekki->id])->assertSessionHas('success');

        $record = Attendance::firstOrFail();
        $this->assertSame($shift->id, $record->staff_schedule_id);
        $this->assertSame(180, $record->late_minutes);
    }

    public function test_forgotten_clock_out_is_closed_at_shift_end_and_flagged(): void
    {
        $desk = $this->makeStaff('Receptionist', $this->lekki);
        $this->shift($desk, $this->lekki); // ends 18:00
        $this->actingAs($desk)->post(route('staff.attendance.clock-in'), ['site_id' => $this->lekki->id]);

        $this->travelTo(now()->addHours(17));
        $this->artisan('operations:check')->assertSuccessful();

        $record = Attendance::firstOrFail();
        $this->assertTrue($record->auto_closed);
        $this->assertSame(today()->subDay()->setTime(18, 0)->toDateTimeString(), $record->clock_out_at->toDateTimeString());
        $this->assertContains('Forgot to clock out', $record->flags());
    }

    // --- Routing --------------------------------------------------------------------------

    public function test_new_repair_goes_to_the_on_shift_technician_at_that_site(): void
    {
        $lekkiTech = $this->makeStaff('Maintenance Technician', $this->lekki);
        $offTech = $this->makeStaff('Maintenance Technician', $this->lekki); // not rostered today
        $abujaTech = $this->makeStaff('Maintenance Technician', $this->abuja);
        $this->shift($lekkiTech, $this->lekki);
        $this->shift($abujaTech, $this->abuja);
        $reporter = $this->makeStaff('Receptionist', $this->lekki);

        $this->actingAs($reporter)->post(route('staff.maintenance.store'), [
            'property_id' => $this->lekkiFlat->id, 'title' => 'AC leaking', 'description' => 'Drips on bed', 'priority' => 'high',
        ])->assertSessionHasNoErrors();

        $report = MaintenanceReport::firstOrFail();
        $this->assertSame($lekkiTech->id, $report->assigned_to);
        $this->assertNotNull($report->assigned_at);
        $this->assertCount(1, $this->alertsFor($lekkiTech));
        $this->assertCount(0, $this->alertsFor($offTech));
        $this->assertCount(0, $this->alertsFor($abujaTech));
    }

    public function test_repair_stays_unassigned_when_no_technician_is_on(): void
    {
        $reporter = $this->makeStaff('Receptionist', $this->lekki);
        $this->shift($reporter, $this->lekki);

        $this->actingAs($reporter)->post(route('staff.maintenance.store'), [
            'property_id' => $this->lekkiFlat->id, 'title' => 'Door', 'description' => 'Stuck', 'priority' => 'low',
        ]);

        $this->assertNull(MaintenanceReport::firstOrFail()->assigned_to, 'A receptionist is never given repairs');
    }

    // --- Escalation -------------------------------------------------------------------------

    public function test_unstarted_urgent_task_escalates_to_supervisor_then_manager_and_head_office(): void
    {
        $supervisor = $this->makeStaff('Site Supervisor', $this->lekki);
        $manager = $this->makeStaff('Operations Manager', $this->lekki);
        $otherSupervisor = $this->makeStaff('Site Supervisor', $this->abuja);
        $task = Task::create(['title' => 'Prepare flat for VIP', 'property_id' => $this->lekkiFlat->id, 'type' => 'checkin_prep', 'priority' => 'urgent', 'status' => 'pending', 'due_date' => today()]);

        $this->travelTo(now()->addMinutes(59));
        $this->artisan('operations:check');
        $this->assertSame(0, $task->fresh()->escalation_level, 'Not yet');

        $this->travelTo(now()->addMinutes(2));
        $this->artisan('operations:check');
        $this->assertSame(1, $task->fresh()->escalation_level);
        $this->assertCount(1, $this->alertsFor($supervisor));
        $this->assertCount(0, $this->alertsFor($manager));
        $this->assertCount(0, $this->alertsFor($otherSupervisor));
        $this->assertCount(0, $this->alertsFor($this->admin));

        $this->artisan('operations:check');
        $this->assertCount(1, $this->alertsFor($supervisor), 'No repeat alert at the same step');

        $this->travelTo(now()->addMinutes(61));
        $this->artisan('operations:check');
        $this->assertSame(2, $task->fresh()->escalation_level);
        $this->assertCount(1, $this->alertsFor($manager));
        $this->assertCount(1, $this->alertsFor($this->admin));
        $this->assertStringContainsString('Still not started', $this->alertsFor($this->admin)->first()->data['title']);
    }

    public function test_site_without_supervisor_escalates_straight_to_head_office(): void
    {
        MaintenanceReport::create(['property_id' => $this->abujaFlat->id, 'reported_by' => $this->admin->id, 'title' => 'Borehole', 'description' => 'No water', 'priority' => 'urgent', 'status' => 'pending']);

        $this->travelTo(now()->addMinutes(61));
        $this->artisan('operations:check');

        $this->assertCount(1, $this->alertsFor($this->admin));
    }

    public function test_starting_or_reassigning_work_resets_escalation(): void
    {
        $cleaner = $this->makeStaff('Cleaner', $this->lekki);
        $task = Task::create(['title' => 'Clean', 'property_id' => $this->lekkiFlat->id, 'type' => 'cleaning', 'priority' => 'urgent', 'status' => 'pending', 'due_date' => today()]);
        $this->travelTo(now()->addMinutes(61));
        $this->artisan('operations:check');
        $this->assertSame(1, $task->fresh()->escalation_level);

        $task->fresh()->update(['assigned_to' => $cleaner->id]);
        $this->assertSame(0, $task->fresh()->escalation_level);

        $this->actingAs($cleaner)->patch(route('staff.tasks.start', $task))->assertSessionHas('success');
        $this->assertNotNull($task->fresh()->started_at);
        $this->travelTo(now()->addHours(5));
        $this->artisan('operations:check');
        $this->assertSame(0, $task->fresh()->escalation_level, 'Work in progress does not escalate');
    }

    public function test_missed_shift_is_reported_once_to_the_supervisor(): void
    {
        $supervisor = $this->makeStaff('Head of Security', $this->lekki);
        $guard = $this->makeStaff('Security Guard', $this->lekki);
        $this->shift($guard, $this->lekki); // 08:00

        $this->travelTo(today()->setTime(8, 25));
        $this->artisan('operations:check');
        $this->assertCount(0, $this->alertsFor($supervisor));

        $this->travelTo(today()->setTime(8, 31));
        $this->artisan('operations:check');
        $this->artisan('operations:check');
        $alerts = $this->alertsFor($supervisor);
        $this->assertCount(1, $alerts);
        $this->assertStringContainsString($guard->name, $alerts->first()->data['title']);
    }

    public function test_no_missed_shift_alert_when_they_clocked_in(): void
    {
        $supervisor = $this->makeStaff('Site Supervisor', $this->lekki);
        $guard = $this->makeStaff('Security Guard', $this->lekki);
        $this->shift($guard, $this->lekki);
        $this->actingAs($guard)->post(route('staff.attendance.clock-in'), ['site_id' => $this->lekki->id]);

        $this->travelTo(today()->setTime(9, 0));
        $this->artisan('operations:check');

        $this->assertCount(0, $this->alertsFor($supervisor));
    }

    // --- Supervisor tools ----------------------------------------------------------------------

    public function test_team_pages_are_for_supervisors_of_their_own_sites(): void
    {
        $supervisor = $this->makeStaff('Site Supervisor', $this->lekki);
        $desk = $this->makeStaff('Receptionist', $this->lekki);
        $abujaGuard = $this->makeStaff('Security Guard', $this->abuja);
        $this->shift($desk, $this->lekki);
        $this->shift($abujaGuard, $this->abuja);

        $this->actingAs($desk)->get(route('staff.team.index'))->assertForbidden();
        $this->actingAs($supervisor)->get(route('staff.team.index'))
            ->assertOk()->assertSee($desk->name)->assertDontSee($abujaGuard->name);
        $this->actingAs($supervisor)->get(route('staff.team.index', ['site' => $this->abuja->id]))->assertNotFound();
        $this->actingAs($supervisor)->get(route('staff.team.roster'))->assertOk()->assertSee($desk->name);
        $this->actingAs($supervisor)->get(route('staff.team.attendance'))->assertOk();
        $this->actingAs($supervisor)->get(route('staff.dashboard'))->assertOk()->assertSee('Team overview');
    }

    public function test_supervisor_assigns_work_only_within_their_site(): void
    {
        $supervisor = $this->makeStaff('Housekeeping Supervisor', $this->lekki);
        $cleaner = $this->makeStaff('Cleaner', $this->lekki);
        $abujaCleaner = $this->makeStaff('Cleaner', $this->abuja);
        $lekkiTask = Task::create(['title' => 'Clean', 'property_id' => $this->lekkiFlat->id, 'type' => 'cleaning', 'priority' => 'medium', 'status' => 'pending', 'due_date' => today()]);
        $abujaTask = Task::create(['title' => 'Clean', 'property_id' => $this->abujaFlat->id, 'type' => 'cleaning', 'priority' => 'medium', 'status' => 'pending', 'due_date' => today()]);

        $this->actingAs($supervisor)->patch(route('staff.team.tasks.assign', $lekkiTask), ['assigned_to' => $abujaCleaner->id])->assertSessionHasErrors('assigned_to');
        $this->actingAs($supervisor)->patch(route('staff.team.tasks.assign', $lekkiTask), ['assigned_to' => $cleaner->id])->assertSessionHas('success');
        $this->assertSame($cleaner->id, $lekkiTask->fresh()->assigned_to);
        $this->assertSame($supervisor->id, $lekkiTask->fresh()->assigned_by);
        $this->assertCount(1, $this->alertsFor($cleaner));

        $this->actingAs($supervisor)->patch(route('staff.team.tasks.assign', $abujaTask), ['assigned_to' => $abujaCleaner->id])->assertNotFound();
    }

    public function test_supervisor_plans_and_cancels_shifts_for_their_site(): void
    {
        $supervisor = $this->makeStaff('Site Supervisor', $this->lekki);
        $guard = $this->makeStaff('Security Guard', $this->lekki);
        $abujaGuard = $this->makeStaff('Security Guard', $this->abuja);

        $this->actingAs($supervisor)->post(route('staff.team.roster.store'), [
            'site_id' => $this->lekki->id, 'staff_id' => $guard->staff->id, 'date' => today()->addDay()->toDateString(), 'shift_type' => 'night',
        ])->assertSessionHas('success');
        $shift = StaffSchedule::where('staff_id', $guard->staff->id)->firstOrFail();
        $this->assertSame($this->lekki->id, $shift->site_id);
        $this->assertSame($supervisor->id, $shift->created_by);

        $this->actingAs($supervisor)->post(route('staff.team.roster.store'), [
            'site_id' => $this->lekki->id, 'staff_id' => $abujaGuard->staff->id, 'date' => today()->addDay()->toDateString(), 'shift_type' => 'night',
        ])->assertSessionHasErrors('staff_id');
        $this->actingAs($supervisor)->post(route('staff.team.roster.store'), [
            'site_id' => $this->abuja->id, 'staff_id' => $abujaGuard->staff->id, 'date' => today()->addDay()->toDateString(), 'shift_type' => 'night',
        ])->assertNotFound();

        $this->actingAs($supervisor)->patch(route('staff.team.roster.cancel', $shift))->assertSessionHas('success');
        $this->assertSame('cancelled', $shift->fresh()->status);
    }

    // --- Head office ---------------------------------------------------------------------------

    public function test_admin_plans_shift_at_a_site_and_sees_attendance_report_and_export(): void
    {
        $desk = $this->makeStaff('Receptionist', $this->lekki);

        $this->actingAs($this->admin)->post(route('admin.staff-schedules.store'), [
            'staff_id' => $desk->staff->id, 'date' => today()->toDateString(), 'shift_type' => 'day',
        ])->assertSessionHasNoErrors();
        $this->assertSame($this->lekki->id, StaffSchedule::firstOrFail()->site_id, 'Defaults to their primary site');

        $this->actingAs($this->admin)->post(route('admin.staff-schedules.store'), [
            'staff_id' => $desk->staff->id, 'site_id' => $this->abuja->id, 'date' => today()->addDay()->toDateString(), 'shift_type' => 'day',
        ])->assertSessionHasErrors('staff_id');

        $this->actingAs($desk)->post(route('staff.attendance.clock-in'), ['site_id' => $this->lekki->id]);
        $this->travelTo(now()->addHours(9));
        $this->actingAs($desk)->post(route('staff.attendance.clock-out'));

        $this->actingAs($this->admin)->get(route('admin.staff-schedules.index', ['site_id' => $this->lekki->id]))->assertOk()->assertSee($desk->name);
        $this->actingAs($this->admin)->get(route('admin.attendance.index'))->assertOk()->assertSee($desk->name)->assertSee('9.0');

        $csv = $this->actingAs($this->admin)->get(route('admin.attendance.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString($desk->name, $csv);
        $this->assertStringContainsString('Lekki Gardens', $csv);
        $this->assertStringContainsString('9.00', $csv);
    }

    public function test_notifications_list_and_open_marks_read(): void
    {
        $cleaner = $this->makeStaff('Cleaner', $this->lekki);
        $cleaner->notify(new OperationsAlert('Clean flat 2', 'Due today', route('staff.tasks.index'), 'assigned', email: false));
        $notification = $cleaner->notifications()->firstOrFail();

        $this->actingAs($cleaner)->get(route('staff.notifications.index'))->assertOk()->assertSee('Clean flat 2');
        $this->actingAs($cleaner)->get(route('staff.notifications.open', $notification->id))->assertRedirect(route('staff.tasks.index'));
        $this->assertNotNull($notification->fresh()->read_at);

        $other = $this->makeStaff('Cleaner', $this->lekki);
        $this->actingAs($other)->get(route('staff.notifications.open', $notification->id))->assertNotFound();
    }

    public function test_site_location_can_be_set_by_admin(): void
    {
        $this->actingAs($this->admin)->put(route('admin.sites.update', $this->abuja), [
            'name' => 'Maitama Court', 'address' => '5 Gana Street', 'city' => 'Abuja', 'state' => 'FCT',
            'latitude' => '9.0820', 'longitude' => '7.4951', 'geofence_radius_m' => 150, 'is_active' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.sites.edit', $this->abuja));

        $site = $this->abuja->fresh();
        $this->assertTrue($site->hasLocation());
        $this->assertSame(150, $site->geofence_radius_m);
        $this->assertLessThan(50, $site->distanceTo(9.0821, 7.4952));
    }
}
