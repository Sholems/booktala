<?php

namespace Tests\Feature;

use App\Models\GuestCheckin;
use App\Models\MaintenanceReport;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\RoomServiceOrder;
use App\Models\Staff;
use App\Models\StaffSchedule;
use App\Models\Task;
use App\Models\User;
use App\Notifications\BookingCheckinReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Phase 2: the hand-offs between the customer, staff and admin portals.
 */
class OperationsFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        foreach (['super_admin', 'admin', 'staff', 'customer'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function makeUser(string $role, array $attrs = []): User
    {
        $user = User::create(array_merge([
            'name' => ucfirst($role) . ' ' . uniqid(),
            'email' => $role . uniqid() . '@example.com',
            'password' => 'password123',
            'status' => 'active',
        ], $attrs));
        $user->assignRole($role);

        if ($role === 'staff') {
            Staff::create([
                'user_id' => $user->id,
                'department' => 'housekeeping',
                'employee_id' => 'T-' . $user->id,
                'hire_date' => today(),
            ]);
        }

        if ($role === 'staff') {
            $this->staffWorkEverywhere();
        }

        return $user;
    }

    private function makeReservation(User $guest, array $attrs = []): Reservation
    {
        $property = Property::create([
            'title' => 'Apartment ' . uniqid(),
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
        ]);

        $this->staffWorkEverywhere();

        return Reservation::create(array_merge([
            'booking_ref' => Reservation::generateBookingRef(),
            'user_id' => $guest->id,
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

    private function onlineCheckinData(): array
    {
        return [
            'guest_first_name' => 'Ada',
            'guest_last_name' => 'Obi',
            'guest_email' => 'ada@example.com',
            'guest_phone' => '08011112222',
            'arrival_time' => '3:00 PM',
            'id_type' => 'national_id',
            'id_number' => 'NIN-12345',
            'num_adults' => 2,
            'emergency_contact_name' => 'Chidi Obi',
            'emergency_contact_phone' => '08033334444',
        ];
    }

    // --- Online check-in -> front desk ---------------------------------------

    public function test_guest_online_check_in_reaches_front_desk_and_is_verified(): void
    {
        $guest = $this->makeUser('customer');
        $staff = $this->makeUser('staff');
        $admin = $this->makeUser('admin');
        $reservation = $this->makeReservation($guest);

        $this->actingAs($guest)->get(route('customer.checkin.index'))->assertOk()->assertSee($reservation->property->title);
        $this->actingAs($guest)->get(route('customer.checkin.create', $reservation))->assertOk();
        $this->actingAs($guest)->post(route('customer.checkin.store', $reservation), $this->onlineCheckinData())
            ->assertSessionHasNoErrors();

        $online = $reservation->guestCheckin()->first();
        $this->assertSame('NIN-12345', $online->id_number);
        $this->actingAs($guest)->get(route('customer.checkin.show', $online))->assertOk();

        // Front desk sees it on today's arrivals and verifies the ID.
        $this->actingAs($staff)->get(route('staff.checkin.index'))->assertOk()->assertSee('Submitted: verify ID');
        $this->actingAs($staff)->get(route('staff.checkin.details', $reservation))->assertOk()->assertSee('NIN-12345');
        $this->actingAs($staff)->patch(route('staff.checkin.verify', $reservation))->assertSessionHas('success');

        $online->refresh();
        $this->assertSame($staff->id, $online->verified_by);
        $this->assertNotNull($online->verified_at);

        // Checking the guest in completes the online check-in; admin sees it all.
        $this->actingAs($staff)->patch(route('staff.checkin.process', $reservation))->assertSessionHas('success');
        $this->assertSame('completed', $online->fresh()->status);
        $this->actingAs($admin)->get(route('admin.reservations.show', $reservation))
            ->assertOk()->assertSee('ID verified by ' . $staff->name);
    }

    public function test_guest_cannot_check_in_online_for_someone_elses_booking(): void
    {
        $reservation = $this->makeReservation($this->makeUser('customer'));
        $intruder = $this->makeUser('customer');

        $this->actingAs($intruder)->get(route('customer.checkin.create', $reservation))->assertForbidden();
        $this->actingAs($intruder)->post(route('customer.checkin.store', $reservation), $this->onlineCheckinData())->assertForbidden();

        $this->assertNull($reservation->guestCheckin()->first());
    }

    public function test_check_in_reminder_nudges_online_check_in(): void
    {
        $guest = $this->makeUser('customer');
        $reservation = $this->makeReservation($guest, [
            'check_in' => today()->addDay()->toDateString(),
            'check_out' => today()->addDays(3)->toDateString(),
        ]);

        $this->artisan('bookings:send-checkin-reminders')->assertSuccessful();

        Notification::assertSentTo($guest, BookingCheckinReminder::class, function ($notification) use ($guest, $reservation) {
            $mail = $notification->toMail($guest);

            return $mail->actionUrl === route('customer.checkin.create', $reservation->id);
        });
    }

    // --- Room service ---------------------------------------------------------

    public function test_room_service_flows_from_guest_to_staff_and_back(): void
    {
        $guest = $this->makeUser('customer');
        $staff = $this->makeUser('staff');
        $reservation = $this->makeReservation($guest, ['booking_status' => 'checked_in']);

        $this->actingAs($guest)->get(route('customer.room-service.create'))->assertOk();
        $this->actingAs($guest)->post(route('customer.room-service.store'), [
            'reservation_id' => $reservation->id,
            'order_type' => 'amenity',
            'items' => [['name' => 'Extra towels', 'quantity' => 2, 'price' => 999999]],
            'special_instructions' => 'After 6pm please',
        ])->assertSessionHasNoErrors();

        $order = RoomServiceOrder::firstOrFail();
        $this->assertSame('pending', $order->status);
        $this->assertSame(0.0, (float) $order->total_amount, 'Guests must not set their own prices');

        $this->actingAs($staff)->get(route('staff.room-service.index'))->assertOk()->assertSee('Extra towels');

        $this->actingAs($staff)->patch(route('staff.room-service.advance', $order), ['charge' => 1500])->assertSessionHas('success');
        $this->actingAs($staff)->patch(route('staff.room-service.advance', $order))->assertSessionHas('success');
        $this->actingAs($staff)->patch(route('staff.room-service.advance', $order))->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('delivered', $order->status);
        $this->assertSame(1500.0, (float) $order->total_amount);
        $this->assertSame($staff->id, $order->handled_by);
        $this->assertNotNull($order->delivered_at);

        // A finished order cannot move further.
        $this->actingAs($staff)->patch(route('staff.room-service.advance', $order))->assertSessionHas('error');

        $this->actingAs($guest)->get(route('customer.room-service.show', $order))->assertOk()->assertSee('1,500.00');
    }

    public function test_room_service_needs_a_checked_in_stay_of_your_own(): void
    {
        $guest = $this->makeUser('customer');
        $notYetArrived = $this->makeReservation($guest);
        $someoneElses = $this->makeReservation($this->makeUser('customer'), ['booking_status' => 'checked_in']);

        foreach ([$notYetArrived, $someoneElses] as $reservation) {
            $this->actingAs($guest)->post(route('customer.room-service.store'), [
                'reservation_id' => $reservation->id,
                'order_type' => 'food',
                'items' => [['name' => 'Water', 'quantity' => 1]],
            ])->assertSessionHasErrors('reservation_id');
        }

        $this->assertSame(0, RoomServiceOrder::count());
    }

    public function test_guest_cannot_view_another_guests_order_and_admin_can_decline(): void
    {
        $guest = $this->makeUser('customer');
        $admin = $this->makeUser('admin');
        $reservation = $this->makeReservation($guest, ['booking_status' => 'checked_in']);
        $order = RoomServiceOrder::create([
            'reservation_id' => $reservation->id,
            'user_id' => $guest->id,
            'order_type' => 'food',
            'items' => [['name' => 'Jollof rice', 'quantity' => 1, 'price' => 0]],
            'total_amount' => 0,
            'status' => 'pending',
        ]);

        $this->actingAs($this->makeUser('customer'))->get(route('customer.room-service.show', $order))->assertForbidden();

        $this->actingAs($admin)->get(route('admin.room-service.index'))->assertOk()->assertSee('Jollof rice');
        $this->actingAs($admin)->patch(route('admin.room-service.decline', $order), ['reason' => 'Kitchen closed'])
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertStringContainsString('Kitchen closed', $order->special_instructions);
    }

    // --- Check-out -> housekeeping -----------------------------------------------

    public function test_check_out_creates_one_cleaning_task_for_staff_on_shift(): void
    {
        $admin = $this->makeUser('admin');
        $onShift = $this->makeUser('staff');
        $this->makeUser('staff'); // off today
        StaffSchedule::create([
            'staff_id' => $onShift->staff->id,
            'date' => today(),
            'shift_type' => 'day',
            'start_time' => '08:00',
            'end_time' => '18:00',
            'status' => 'scheduled',
        ]);
        $reservation = $this->makeReservation($this->makeUser('customer'), ['booking_status' => 'checked_in']);

        $this->actingAs($admin)->patch(route('admin.reservations.checkout', $reservation))->assertSessionHas('success');

        $task = Task::where('reservation_id', $reservation->id)->firstOrFail();
        $this->assertSame('cleaning', $task->type);
        $this->assertSame($onShift->id, $task->assigned_to);
        $this->assertSame($reservation->property_id, $task->property_id);
        $this->assertSame(1, Task::where('reservation_id', $reservation->id)->count());

        $this->actingAs($onShift)->get(route('staff.tasks.index'))->assertOk()->assertSee('after check-out');
    }

    public function test_cleaning_task_is_unassigned_when_nobody_is_on_shift(): void
    {
        $staff = $this->makeUser('staff');
        $reservation = $this->makeReservation($this->makeUser('customer'), ['booking_status' => 'checked_in']);

        $this->actingAs($staff)->patch(route('staff.checkout.process', $reservation))->assertSessionHas('success');

        $task = Task::where('reservation_id', $reservation->id)->firstOrFail();
        $this->assertNull($task->assigned_to);
    }

    // --- Maintenance ------------------------------------------------------------

    public function test_maintenance_report_assign_and_resolve(): void
    {
        $admin = $this->makeUser('admin');
        $reporter = $this->makeUser('staff');
        $fixer = $this->makeUser('staff');
        $property = $this->makeReservation($this->makeUser('customer'))->property;

        // "emergency" from older forms is stored as "urgent".
        $this->actingAs($reporter)->post(route('staff.maintenance.store'), [
            'property_id' => $property->id,
            'title' => 'Leaking tap',
            'description' => 'Kitchen tap drips constantly',
            'priority' => 'emergency',
        ])->assertSessionHasNoErrors();

        $report = MaintenanceReport::firstOrFail();
        $this->assertSame('urgent', $report->priority);

        $this->actingAs($admin)->get(route('admin.maintenance.index'))->assertOk()->assertSee('Leaking tap');
        $this->actingAs($admin)->patch(route('admin.maintenance.update', $report), [
            'status' => 'pending',
            'assigned_to' => $fixer->id,
        ])->assertSessionHasNoErrors();
        $this->assertSame($fixer->id, $report->fresh()->assigned_to);

        // Only the assignee can update it, and resolving needs a note.
        $this->actingAs($reporter)->patch(route('staff.maintenance.update', $report), ['status' => 'in_progress'])->assertForbidden();
        $this->actingAs($fixer)->get(route('staff.maintenance.index'))->assertOk()->assertSee('Assigned to me')->assertSee('Leaking tap');
        $this->actingAs($fixer)->patch(route('staff.maintenance.update', $report), ['status' => 'resolved'])
            ->assertSessionHasErrors('resolution_notes');
        $this->actingAs($fixer)->patch(route('staff.maintenance.update', $report), [
            'status' => 'resolved',
            'resolution_notes' => 'Replaced washer',
        ])->assertSessionHas('success');

        $report->refresh();
        $this->assertSame('resolved', $report->status);
        $this->assertSame('Replaced washer', $report->resolution_notes);
        $this->assertNotNull($report->resolved_at);
    }

    // --- Staff schedules ----------------------------------------------------------

    public function test_admin_schedules_a_shift_and_staff_sees_it(): void
    {
        $admin = $this->makeUser('admin');

        // Adding a staff member creates the profile shifts are planned against.
        $this->actingAs($admin)->post(route('admin.staff.store'), [
            'name' => 'Bola Ade',
            'email' => 'bola@example.com',
            'position_id' => \App\Models\Position::where('name', 'Receptionist')->value('id'),
            'site_id' => \App\Models\Site::create(['name' => 'HQ', 'address' => '1 Road', 'city' => 'Lagos', 'state' => 'Lagos'])->id,
            'password' => 'Secret1234',
        ])->assertSessionHasNoErrors();
        $staff = User::where('email', 'bola@example.com')->firstOrFail();
        $this->assertNotNull($staff->staff);

        $this->actingAs($admin)->post(route('admin.staff-schedules.store'), [
            'staff_id' => $staff->staff->id,
            'date' => today()->addDay()->toDateString(),
            'shift_type' => 'morning',
            'notes' => 'Cover front desk',
        ])->assertSessionHasNoErrors();

        $this->actingAs($staff)->get(route('staff.schedule.index'))
            ->assertOk()
            ->assertSee(today()->addDay()->format('l, j M Y'))
            ->assertSee('Cover front desk');
    }
}
