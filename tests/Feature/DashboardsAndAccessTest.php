<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\GuestCheckin;
use App\Models\Message;
use App\Models\Property;
use App\Models\Reservation;
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
 * Phase 3: action-focused dashboards, the shared guest inbox and permission checks.
 */
class DashboardsAndAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeUser(string $role): User
    {
        $user = User::create([
            'name' => ucfirst($role).' '.uniqid(),
            'email' => $role.uniqid().'@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);
        $user->assignRole($role);

        if ($role === 'staff') {
            Staff::create(['user_id' => $user->id, 'department' => 'frontdesk', 'employee_id' => 'T-'.$user->id, 'hire_date' => today()]);
        }

        if ($role === 'staff') {
            $this->staffWorkEverywhere();
        }

        return $user;
    }

    private function makeReservation(User $guest, array $attrs = []): Reservation
    {
        $property = Property::create([
            'title' => 'Apartment '.uniqid(),
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

    // --- Admin dashboard ----------------------------------------------------------

    public function test_admin_dashboard_counts_work_queues_and_links_to_them(): void
    {
        $admin = $this->makeUser('admin');
        $guest = $this->makeUser('customer');
        $arriving = $this->makeReservation($guest);
        $toConfirm = $this->makeReservation($guest, [
            'booking_status' => 'pending',
            'check_in' => today()->addDays(5)->toDateString(),
            'check_out' => today()->addDays(7)->toDateString(),
        ]);
        $toConfirm->update(['confirmed_at' => null]);
        $arriving->update(['confirmed_at' => now(), 'confirmed_by' => $admin->id]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $response->assertSee('Needs attention')
            ->assertSee('Arriving today')
            ->assertSee(route('admin.reservations.index', ['queue' => 'arrivals']), false)
            ->assertSee('Recent activity')
            ->assertSee('Booking '.$arriving->booking_ref.' confirmed by '.$admin->name);

        $this->actingAs($admin)->get(route('admin.reservations.index', ['queue' => 'arrivals']))
            ->assertOk()->assertSee($arriving->booking_ref)->assertDontSee($toConfirm->booking_ref);
        $this->actingAs($admin)->get(route('admin.reservations.index', ['queue' => 'to_confirm']))
            ->assertOk()->assertSee($toConfirm->booking_ref)->assertDontSee($arriving->booking_ref);
    }

    public function test_admin_can_assign_an_unassigned_task_from_the_list(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');
        $property = $this->makeReservation($this->makeUser('customer'))->property;
        $task = Task::create([
            'title' => 'Deep clean',
            'property_id' => $property->id,
            'type' => 'cleaning',
            'priority' => 'medium',
            'status' => 'pending',
            'due_date' => today(),
        ]);

        $this->actingAs($admin)->get(route('admin.tasks.index', ['show' => 'unassigned']))->assertOk()->assertSee('Deep clean');
        $this->actingAs($admin)->patch(route('admin.tasks.update', $task), ['assigned_to' => $staff->id])->assertSessionHasNoErrors();
        $this->assertSame($staff->id, $task->fresh()->assigned_to);

        // Only staff can be assigned.
        $this->actingAs($admin)->patch(route('admin.tasks.update', $task), ['assigned_to' => $admin->id])->assertSessionHasErrors('assigned_to');
    }

    public function test_staff_and_admin_hold_a_note_thread_on_a_task(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');
        $other = $this->makeUser('staff');
        $property = $this->makeReservation($this->makeUser('customer'))->property;
        $task = Task::create([
            'title' => 'Fix curtain rail',
            'property_id' => $property->id,
            'assigned_to' => $staff->id,
            'assigned_by' => $admin->id,
            'type' => 'maintenance',
            'priority' => 'medium',
            'status' => 'in_progress',
            'due_date' => today(),
        ]);

        // Staff leaves a note; admin sees it flagged on the list and on the task page.
        $this->actingAs($staff)->post(route('staff.tasks.notes', $task), ['notes' => 'Need a new bracket'])->assertSessionHas('success');
        $this->actingAs($admin)->get(route('admin.tasks.index'))->assertOk()->assertSee('New note from staff');
        $this->actingAs($admin)->get(route('admin.tasks.show', $task))->assertOk()->assertSee('Need a new bracket');

        // Admin replies in the same thread; staff sees the reply.
        $this->actingAs($admin)->post(route('admin.tasks.notes', $task), ['notes' => 'Buy one, keep the receipt'])->assertSessionHas('success');
        $this->actingAs($admin)->get(route('admin.tasks.index'))->assertOk()->assertDontSee('New note from staff');
        $this->actingAs($staff)->get(route('staff.tasks.index'))->assertOk()->assertSee('Admin replied');
        $this->actingAs($staff)->get(route('staff.tasks.show', $task))->assertOk()
            ->assertSeeInOrder(['Need a new bracket', 'Buy one, keep the receipt']);

        $this->assertSame([$staff->id, $admin->id], $task->taskNotes()->pluck('user_id')->all());

        // Other staff can't read or post into it; customers can't reach the admin side.
        $this->actingAs($other)->get(route('staff.tasks.show', $task))->assertForbidden();
        $this->actingAs($other)->post(route('staff.tasks.notes', $task), ['notes' => 'hi'])->assertForbidden();
        $this->actingAs($this->makeUser('customer'))->post(route('admin.tasks.notes', $task), ['notes' => 'hi'])->assertForbidden();
        $this->assertSame(2, $task->taskNotes()->count());
    }

    // --- Staff dashboard -----------------------------------------------------------

    public function test_staff_dashboard_shows_shift_and_todays_work(): void
    {
        $staff = $this->makeUser('staff');
        StaffSchedule::create([
            'staff_id' => $staff->staff->id,
            'date' => today(),
            'shift_type' => 'morning',
            'start_time' => '06:00',
            'end_time' => '14:00',
            'status' => 'scheduled',
        ]);
        $this->makeReservation($this->makeUser('customer'));

        $this->actingAs($staff)->get(route('staff.dashboard'))
            ->assertOk()
            ->assertSee('On shift today')
            ->assertSee('6:00 AM')
            ->assertSee('Arrivals today')
            ->assertSee('Room service waiting');
    }

    // --- Customer dashboard and booking timeline ------------------------------------

    public function test_customer_sees_next_step_for_upcoming_stay(): void
    {
        $guest = $this->makeUser('customer');
        $reservation = $this->makeReservation($guest, [
            'check_in' => today()->addDays(3)->toDateString(),
            'check_out' => today()->addDays(5)->toDateString(),
        ]);

        $this->actingAs($guest)->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Your next stay')
            ->assertSee('Check in online')
            ->assertSee(route('customer.checkin.create', $reservation), false);

        $this->actingAs($guest)->get(route('customer.reservations.show', $reservation))
            ->assertOk()
            ->assertSee('Booking progress', false)
            ->assertSee('Online check-in');

        // Once online check-in is done the prompt changes.
        GuestCheckin::create([
            'reservation_id' => $reservation->id,
            'check_in_date' => $reservation->check_in,
            'guest_first_name' => 'A', 'guest_last_name' => 'B', 'guest_email' => 'a@b.com', 'guest_phone' => '1',
            'id_type' => 'passport', 'id_number' => 'X1', 'num_adults' => 1,
            'emergency_contact_name' => 'C', 'emergency_contact_phone' => '2', 'status' => 'pending',
        ]);
        $this->actingAs($guest)->get(route('customer.dashboard'))->assertOk()->assertSee("You're all set");
    }

    public function test_checked_in_guest_is_offered_room_service(): void
    {
        $guest = $this->makeUser('customer');
        $this->makeReservation($guest, ['booking_status' => 'checked_in']);

        $this->actingAs($guest)->get(route('customer.dashboard'))
            ->assertOk()->assertSee('Current stay')->assertSee('Request room service');
    }

    // --- Shared guest inbox ------------------------------------------------------------

    public function test_guest_message_reaches_team_inbox_and_reply_reaches_guest(): void
    {
        $guest = $this->makeUser('customer');
        $staff = $this->makeUser('staff');
        $admin = $this->makeUser('admin');
        $reservation = $this->makeReservation($guest);

        $this->actingAs($guest)->post(route('customer.messages.store'), [
            'message' => 'What time can I check in?',
            'reservation_id' => $reservation->id,
        ])->assertSessionHasNoErrors();

        $conversation = Conversation::firstOrFail();
        $this->assertSame($guest->id, $conversation->user_id);

        // Admin dashboard counts it; staff can read it, which marks it read for the team.
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Unread guest messages');
        $this->actingAs($staff)->get(route('staff.messages.index'))->assertOk()->assertSee('1 new');
        $this->actingAs($staff)->get(route('staff.messages.show', $conversation))->assertOk()->assertSee('What time can I check in?');
        $this->assertSame(0, Message::where('read', false)->count());

        $this->actingAs($staff)->post(route('staff.messages.reply', $conversation), ['message' => 'From 2pm.'])->assertSessionHas('success');
        $reply = Message::latest('id')->first();
        $this->assertSame($guest->id, $reply->receiver_id);

        $this->actingAs($guest)->get(route('customer.messages.show', $conversation))->assertOk()->assertSee('From 2pm.');
    }

    public function test_guest_cannot_read_or_post_into_another_guests_conversation(): void
    {
        $owner = $this->makeUser('customer');
        $intruder = $this->makeUser('customer');
        $conversation = Conversation::create(['user_id' => $owner->id, 'last_message_at' => now()]);

        $this->actingAs($intruder)->get(route('customer.messages.show', $conversation))->assertForbidden();
        $this->actingAs($intruder)->post(route('customer.messages.reply', $conversation), ['message' => 'hi'])->assertForbidden();

        // Nor attach a message to someone else's booking.
        $othersBooking = $this->makeReservation($owner);
        $this->actingAs($intruder)->post(route('customer.messages.store'), [
            'message' => 'hello',
            'reservation_id' => $othersBooking->id,
        ])->assertSessionHasErrors('reservation_id');
    }

    // --- Permissions -------------------------------------------------------------------

    public function test_admin_role_is_limited_by_its_permissions(): void
    {
        $admin = $this->makeUser('admin');
        $superAdmin = $this->makeUser('super_admin');

        // The admin role does not include settings; super admins can do everything.
        $this->actingAs($admin)->get(route('admin.settings.index'))->assertForbidden();
        $this->actingAs($superAdmin)->get(route('admin.settings.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertDontSee(route('admin.settings.index'), false);

        // Removing a permission from the role takes effect without code changes.
        Role::findByName('admin')->revokePermissionTo('reservation.refund');
        $reservation = $this->makeReservation($this->makeUser('customer'), ['booking_status' => 'cancelled', 'refund_status' => 'pending']);
        $this->actingAs($admin->fresh())->patch(route('admin.reservations.refund', $reservation), ['refund_status' => 'refunded'])
            ->assertForbidden();
        $this->assertSame('pending', $reservation->fresh()->refund_status);
    }

    public function test_unpaid_check_in_override_follows_the_permission(): void
    {
        $admin = $this->makeUser('admin');
        Role::findByName('admin')->revokePermissionTo('reservation.checkin-unpaid');
        $reservation = $this->makeReservation($this->makeUser('customer'), ['payment_status' => 'pending']);

        $this->actingAs($admin->fresh())->patch(route('admin.reservations.checkin', $reservation), ['allow_unpaid' => 1])
            ->assertSessionHas('error');
        $this->assertSame('confirmed', $reservation->fresh()->booking_status);
    }
}
