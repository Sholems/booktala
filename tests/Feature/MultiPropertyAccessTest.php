<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\HousekeepingInspection;
use App\Models\MaintenanceReport;
use App\Models\Message;
use App\Models\Owner;
use App\Models\Position;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\RoomServiceOrder;
use App\Models\Site;
use App\Models\Staff;
use App\Models\StaffSchedule;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Multi-property foundation: owners, sites, positions and staff assignments,
 * and the rule that staff only see and act on their own sites' data.
 *
 * Two sites throughout: Lekki (where $lekkiDesk works) and Abuja.
 */
class MultiPropertyAccessTest extends TestCase
{
    use RefreshDatabase;

    private Site $lekki;

    private Site $abuja;

    private Property $lekkiFlat;

    private Property $abujaFlat;

    private User $admin;

    private User $lekkiDesk;

    private User $guest;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->seed(RolePermissionSeeder::class);

        $this->lekki = Site::create(['name' => 'Lekki Gardens', 'address' => '1 Admiralty Way', 'city' => 'Lekki', 'state' => 'Lagos']);
        $this->abuja = Site::create(['name' => 'Maitama Court', 'address' => '5 Gana Street', 'city' => 'Abuja', 'state' => 'FCT']);
        $this->lekkiFlat = $this->makeProperty('Lekki Flat', $this->lekki);
        $this->abujaFlat = $this->makeProperty('Abuja Flat', $this->abuja);

        $this->admin = $this->makeUser('admin');
        $this->guest = $this->makeUser('customer');
        $this->lekkiDesk = $this->makeStaff('Receptionist', $this->lekki);
    }

    // --- Helpers -------------------------------------------------------------------

    private function makeUser(string $role): User
    {
        $user = User::create([
            'name' => ucfirst($role).' '.uniqid(),
            'email' => $role.uniqid().'@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function makeStaff(string $position, ?Site $site): User
    {
        $user = $this->makeUser('staff');
        $staff = Staff::create(['user_id' => $user->id, 'employee_id' => 'T-'.$user->id, 'hire_date' => today()]);
        if ($site) {
            $staff->assignTo($site, Position::where('name', $position)->firstOrFail());
        }

        return $user->fresh();
    }

    private function makeProperty(string $title, ?Site $site = null): Property
    {
        return Property::create(array_filter([
            'title' => $title.' '.uniqid(),
            'description' => 'Nice place',
            'address' => '1 Test Street',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'price_per_night' => 10000,
            'max_guests' => 4,
            'bedrooms' => 2,
            'bathrooms' => 2,
            'toilets' => 2,
            'status' => 'active',
            'site_id' => $site?->id,
        ]));
    }

    private function makeReservation(Property $property, array $attrs = []): Reservation
    {
        return Reservation::create(array_merge([
            'booking_ref' => Reservation::generateBookingRef(),
            'user_id' => $this->guest->id,
            'property_id' => $property->id,
            'check_in' => today()->toDateString(),
            'check_out' => today()->addDays(2)->toDateString(),
            'nights' => 2,
            'guests' => 2,
            'subtotal' => 20000,
            'total_amount' => 21500,
            'payment_status' => 'paid',
            'booking_status' => 'confirmed',
        ], $attrs));
    }

    // --- Owners and sites ------------------------------------------------------------

    public function test_new_property_defaults_to_company_owner_and_its_own_site(): void
    {
        $property = $this->makeProperty('Standalone');

        $this->assertTrue($property->owner->is_company_owned);
        $this->assertNotNull($property->site);
        $this->assertSame(1, $property->site->properties()->count());
        $this->assertSame(1, Owner::where('is_company_owned', true)->count());
    }

    public function test_admin_can_group_a_flat_into_a_building_and_its_old_site_is_removed(): void
    {
        $flat = $this->makeProperty('Loose flat');
        $oldSite = $flat->site;

        $this->actingAs($this->admin)
            ->post(route('admin.sites.properties.attach', $this->lekki), ['property_id' => $flat->id])
            ->assertSessionHas('success');

        $this->assertSame($this->lekki->id, $flat->fresh()->site_id);
        $this->assertModelMissing($oldSite);
    }

    public function test_admin_manages_owners_sites_and_positions(): void
    {
        $this->actingAs($this->admin)->post(route('admin.owners.store'), [
            'name' => 'Mrs Adeyemi', 'type' => 'individual', 'phone' => '0803', 'is_active' => 1,
        ])->assertSessionHasNoErrors();
        $landlord = Owner::where('name', 'Mrs Adeyemi')->firstOrFail();

        $this->actingAs($this->admin)->put(route('admin.properties.update', $this->abujaFlat), array_merge(
            $this->abujaFlat->only(['title', 'slug', 'description', 'address', 'city', 'state', 'price_per_night', 'max_guests', 'bedrooms', 'bathrooms', 'toilets', 'status']),
            ['is_featured' => 0, 'owner_id' => $landlord->id, 'site_id' => '']
        ))->assertSessionHasNoErrors();
        $this->assertSame($landlord->id, $this->abujaFlat->fresh()->owner_id);
        $this->assertSame($this->abuja->id, $this->abujaFlat->fresh()->site_id, 'Blank site keeps the current one');

        $this->actingAs($this->admin)->post(route('admin.positions.store'), [
            'name' => 'Night Guard', 'department' => 'security', 'level' => 'staff',
        ])->assertSessionHasNoErrors();

        foreach (['admin.owners.index', 'admin.sites.index', 'admin.positions.index', 'admin.sites.create', 'admin.owners.create'] as $route) {
            $this->actingAs($this->admin)->get(route($route))->assertOk();
        }
        $this->actingAs($this->admin)->get(route('admin.sites.edit', $this->lekki))->assertOk()->assertSee($this->lekkiDesk->name);
        $this->actingAs($this->admin)->get(route('admin.owners.edit', $landlord))->assertOk()->assertSee($this->abujaFlat->title);
        $this->actingAs($this->admin)->get(route('admin.properties.edit', $this->abujaFlat))->assertOk()->assertSee('Mrs Adeyemi');
    }

    public function test_company_owner_and_used_sites_cannot_be_deleted(): void
    {
        $this->actingAs($this->admin)->delete(route('admin.owners.destroy', Owner::company()))->assertSessionHas('error');
        $this->actingAs($this->admin)->delete(route('admin.sites.destroy', $this->lekki))->assertSessionHas('error');
        $this->assertModelExists($this->lekki);
    }

    public function test_staff_cannot_open_owner_site_or_position_screens(): void
    {
        $this->actingAs($this->lekkiDesk)->get(route('admin.sites.index'))->assertForbidden();
        $this->actingAs($this->lekkiDesk)->get(route('admin.owners.index'))->assertForbidden();
        $this->actingAs($this->lekkiDesk)->get(route('admin.positions.index'))->assertForbidden();
    }

    // --- Staff assignments -------------------------------------------------------------

    public function test_admin_creates_staff_with_a_position_and_site(): void
    {
        $this->actingAs($this->admin)->post(route('admin.staff.store'), [
            'name' => 'Musa Guard',
            'email' => 'musa@example.com',
            'position_id' => Position::where('name', 'Security Guard')->value('id'),
            'site_id' => $this->abuja->id,
            'password' => 'Secret1234',
        ])->assertSessionHasNoErrors();

        $staff = User::where('email', 'musa@example.com')->firstOrFail()->staff;
        $assignment = $staff->primaryAssignment;
        $this->assertSame($this->abuja->id, $assignment->site_id);
        $this->assertSame('Security Guard', $assignment->position->name);
        $this->assertSame('security', $staff->department);
    }

    public function test_a_technician_can_cover_several_sites_and_ending_one_removes_access(): void
    {
        $tech = $this->makeStaff('Maintenance Technician', $this->lekki);
        $this->makeReservation($this->abujaFlat);

        $this->actingAs($this->admin)->post(route('admin.staff.assignments.store', $tech), [
            'site_id' => $this->abuja->id,
            'position_id' => Position::where('name', 'Maintenance Technician')->value('id'),
        ])->assertSessionHas('success');
        $this->actingAs($tech)->get(route('staff.checkin.index'))->assertSee($this->abujaFlat->title);

        $abujaPost = $tech->staff->activeAssignments()->where('site_id', $this->abuja->id)->firstOrFail();
        $abujaPost->update(['starts_on' => today()->subMonth()]); // worked there a while
        $this->actingAs($this->admin)->patch(route('admin.staff.assignments.end', [$tech, $abujaPost]))->assertSessionHas('success');

        $this->assertNotNull($abujaPost->fresh()->ends_on, 'Ended assignments stay in the history');
        $this->actingAs($tech)->get(route('staff.checkin.index'))->assertDontSee($this->abujaFlat->title);
    }

    public function test_admin_cannot_end_another_staff_members_assignment_through_the_wrong_url(): void
    {
        $other = $this->makeStaff('Cleaner', $this->abuja);
        $assignment = $other->staff->activeAssignments()->firstOrFail();

        $this->actingAs($this->admin)
            ->patch(route('admin.staff.assignments.end', [$this->lekkiDesk, $assignment]))
            ->assertNotFound();
    }

    // --- What staff can see ------------------------------------------------------------------

    public function test_front_desk_sees_only_their_sites_arrivals_and_departures(): void
    {
        $this->makeReservation($this->lekkiFlat);
        $this->makeReservation($this->abujaFlat);
        $this->makeReservation($this->abujaFlat, ['booking_status' => 'checked_in', 'check_in' => today()->subDays(2)->toDateString(), 'check_out' => today()->toDateString()]);

        $this->actingAs($this->lekkiDesk)->get(route('staff.checkin.index'))
            ->assertOk()->assertSee($this->lekkiFlat->title)->assertDontSee($this->abujaFlat->title);
        $this->actingAs($this->lekkiDesk)->get(route('staff.checkout.index'))
            ->assertOk()->assertDontSee($this->abujaFlat->title);
        $this->actingAs($this->lekkiDesk)->get(route('staff.dashboard'))
            ->assertOk()->assertSee($this->lekkiFlat->title)->assertDontSee($this->abujaFlat->title);
    }

    public function test_booking_search_does_not_find_other_sites_guests(): void
    {
        $abujaBooking = $this->makeReservation($this->abujaFlat);

        // (The search box echoes the term, so check for the booking's details instead.)
        $this->actingAs($this->lekkiDesk)->get(route('staff.checkin.search', ['search' => $abujaBooking->booking_ref]))
            ->assertOk()->assertDontSee($this->abujaFlat->title)->assertDontSee($this->guest->email);

        $abujaDesk = $this->makeStaff('Receptionist', $this->abuja);
        $this->actingAs($abujaDesk)->get(route('staff.checkin.search', ['search' => $abujaBooking->booking_ref]))
            ->assertOk()->assertSee($this->abujaFlat->title);
    }

    public function test_staff_cannot_act_on_another_sites_booking(): void
    {
        $abujaBooking = $this->makeReservation($this->abujaFlat);

        $this->actingAs($this->lekkiDesk)->get(route('staff.checkin.details', $abujaBooking))->assertNotFound();
        $this->actingAs($this->lekkiDesk)->patch(route('staff.checkin.process', $abujaBooking))->assertNotFound();
        $this->actingAs($this->lekkiDesk)->patch(route('staff.checkin.verify', $abujaBooking))->assertNotFound();
        $this->actingAs($this->lekkiDesk)->patch(route('staff.checkout.process', $abujaBooking))->assertNotFound();
        $this->assertSame('confirmed', $abujaBooking->fresh()->booking_status);
    }

    public function test_staff_with_no_assignment_sees_nothing(): void
    {
        $unassigned = $this->makeStaff('Receptionist', null);
        $this->makeReservation($this->lekkiFlat);

        $this->actingAs($unassigned)->get(route('staff.checkin.index'))->assertOk()->assertDontSee($this->lekkiFlat->title);
    }

    public function test_site_scoped_admin_cannot_access_another_sites_housekeeping_inspections(): void
    {
        Role::findByName('admin')->revokePermissionTo('portfolio.view_all');
        $staff = Staff::create([
            'user_id' => $this->admin->id,
            'employee_id' => 'OPS-'.$this->admin->id,
            'hire_date' => today(),
            'is_active' => true,
        ]);
        $staff->assignTo($this->lekki, Position::where('name', 'Receptionist')->firstOrFail());

        $lekkiInspection = HousekeepingInspection::create([
            'unit_id' => $this->lekkiFlat->id,
            'cleaner_id' => $this->lekkiDesk->id,
            'status' => HousekeepingInspection::STATUS_PENDING_REVIEW,
            'checklist' => HousekeepingInspection::defaultChecklist(),
            'submitted_at' => now(),
        ]);
        $abujaInspection = HousekeepingInspection::create([
            'unit_id' => $this->abujaFlat->id,
            'cleaner_id' => $this->lekkiDesk->id,
            'status' => HousekeepingInspection::STATUS_PENDING_REVIEW,
            'checklist' => HousekeepingInspection::defaultChecklist(),
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->admin)->get(route('admin.inspections.index'))
            ->assertOk()
            ->assertSee(route('admin.inspections.show', $lekkiInspection))
            ->assertDontSee(route('admin.inspections.show', $abujaInspection));
        $this->actingAs($this->admin)->get(route('admin.inspections.show', $abujaInspection))->assertNotFound();
        $this->actingAs($this->admin)->patch(route('admin.inspections.approve', $abujaInspection))->assertNotFound();
        $this->actingAs($this->admin)->patch(route('admin.inspections.reject', $abujaInspection), ['notes' => 'No access'])->assertNotFound();
        $this->assertSame(HousekeepingInspection::STATUS_PENDING_REVIEW, $abujaInspection->fresh()->status);
    }

    public function test_maintenance_is_limited_to_the_staff_members_sites(): void
    {
        MaintenanceReport::create(['property_id' => $this->abujaFlat->id, 'reported_by' => $this->admin->id, 'title' => 'Abuja borehole pump', 'description' => 'No water', 'priority' => 'high', 'status' => 'pending']);
        MaintenanceReport::create(['property_id' => $this->lekkiFlat->id, 'reported_by' => $this->admin->id, 'title' => 'Lekki AC leaking', 'description' => 'Drips', 'priority' => 'medium', 'status' => 'pending']);

        $this->actingAs($this->lekkiDesk)->get(route('staff.maintenance.index'))
            ->assertOk()->assertSee('Lekki AC leaking')->assertDontSee('Abuja borehole pump')->assertDontSee($this->abujaFlat->title);

        $this->actingAs($this->lekkiDesk)->post(route('staff.maintenance.store'), [
            'property_id' => $this->abujaFlat->id, 'title' => 'x', 'description' => 'y', 'priority' => 'low',
        ])->assertSessionHasErrors('property_id');
    }

    public function test_room_service_queue_is_limited_to_the_staff_members_sites(): void
    {
        $abujaStay = $this->makeReservation($this->abujaFlat, ['booking_status' => 'checked_in']);
        $order = RoomServiceOrder::create([
            'reservation_id' => $abujaStay->id, 'user_id' => $this->guest->id, 'order_type' => 'amenity',
            'items' => [['name' => 'Abuja towels', 'quantity' => 1]], 'total_amount' => 0, 'status' => 'pending',
        ]);

        $this->actingAs($this->lekkiDesk)->get(route('staff.room-service.index'))->assertOk()->assertDontSee('Abuja towels');
        $this->actingAs($this->lekkiDesk)->patch(route('staff.room-service.advance', $order))->assertNotFound();
        $this->assertSame('pending', $order->fresh()->status);

        $this->actingAs($this->admin)->get(route('admin.room-service.index'))->assertOk()->assertSee('Abuja towels');
    }

    public function test_guest_inbox_routes_messages_to_the_right_site(): void
    {
        $abujaBooking = $this->makeReservation($this->abujaFlat);
        $this->actingAs($this->guest)->post(route('customer.messages.store'), [
            'message' => 'Is the generator on tonight?',
            'reservation_id' => $abujaBooking->id,
        ])->assertSessionHasNoErrors();
        $conversation = Conversation::firstOrFail();
        $this->assertSame($this->abujaFlat->id, $conversation->property_id, 'Booking messages are tied to the booking\'s property');

        $general = Conversation::create(['user_id' => $this->guest->id, 'last_message_at' => now()]);
        Message::create(['conversation_id' => $general->id, 'sender_id' => $this->guest->id, 'message' => 'General enquiry', 'read' => false]);

        $this->actingAs($this->lekkiDesk)->get(route('staff.messages.index'))
            ->assertOk()->assertDontSee($this->abujaFlat->title);
        $this->actingAs($this->lekkiDesk)->get(route('staff.messages.show', $conversation))->assertNotFound();
        $this->actingAs($this->lekkiDesk)->post(route('staff.messages.reply', $conversation), ['message' => 'Hi'])->assertNotFound();
        $this->actingAs($this->lekkiDesk)->get(route('staff.messages.show', $general))->assertNotFound();

        $abujaDesk = $this->makeStaff('Receptionist', $this->abuja);
        $this->actingAs($abujaDesk)->get(route('staff.messages.show', $conversation))->assertOk()->assertSee('Is the generator on tonight?');
        $this->actingAs($this->admin)->get(route('admin.messages.show', $general))->assertOk()->assertSee('General enquiry');
    }

    // --- Assigning work ------------------------------------------------------------------------------

    public function test_admin_can_only_assign_work_to_staff_at_that_site(): void
    {
        $lekkiTask = Task::create(['title' => 'Inspect', 'property_id' => $this->lekkiFlat->id, 'type' => 'inspection', 'priority' => 'medium', 'status' => 'pending', 'due_date' => today()]);
        $abujaTask = Task::create(['title' => 'Inspect', 'property_id' => $this->abujaFlat->id, 'type' => 'inspection', 'priority' => 'medium', 'status' => 'pending', 'due_date' => today()]);

        $this->actingAs($this->admin)->patch(route('admin.tasks.update', $lekkiTask), ['assigned_to' => $this->lekkiDesk->id])->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->patch(route('admin.tasks.update', $abujaTask), ['assigned_to' => $this->lekkiDesk->id])->assertSessionHasErrors('assigned_to');
        $this->assertNull($abujaTask->fresh()->assigned_to);

        $this->actingAs($this->admin)->post(route('admin.maintenance.store'), [
            'title' => 'Roof', 'property_id' => $this->abujaFlat->id, 'description' => 'Leak', 'assigned_to' => $this->lekkiDesk->id,
        ])->assertSessionHasErrors('assigned_to');
    }

    public function test_cleaning_after_checkout_goes_to_a_cleaner_at_that_site(): void
    {
        $abujaCleaner = $this->makeStaff('Cleaner', $this->abuja);
        $lekkiCleaner = $this->makeStaff('Cleaner', $this->lekki);
        foreach ([$abujaCleaner, $lekkiCleaner, $this->lekkiDesk] as $user) {
            StaffSchedule::create(['staff_id' => $user->staff->id, 'date' => today(), 'shift_type' => 'day', 'start_time' => '08:00', 'end_time' => '18:00', 'status' => 'scheduled']);
        }
        // Give the Lekki cleaner a lighter load than the Lekki receptionist so only the department decides.
        Task::create(['title' => 'Busy', 'property_id' => $this->lekkiFlat->id, 'assigned_to' => $lekkiCleaner->id, 'type' => 'cleaning', 'priority' => 'low', 'status' => 'pending', 'due_date' => today()]);

        $lekkiStay = $this->makeReservation($this->lekkiFlat, ['booking_status' => 'checked_in']);
        $this->actingAs($this->admin)->patch(route('admin.reservations.checkout', $lekkiStay))->assertSessionHas('success');

        $task = Task::where('reservation_id', $lekkiStay->id)->firstOrFail();
        $this->assertSame($lekkiCleaner->id, $task->assigned_to, 'Housekeeping at the same site, never the Abuja cleaner');
    }

    public function test_head_office_sees_every_site(): void
    {
        $this->makeReservation($this->lekkiFlat);
        $this->makeReservation($this->abujaFlat);

        $this->actingAs($this->admin)->get(route('admin.reservations.index'))
            ->assertOk()->assertSee($this->lekkiFlat->title)->assertSee($this->abujaFlat->title);
    }
}
