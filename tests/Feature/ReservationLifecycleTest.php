<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Task;
use App\Models\User;
use App\Notifications\BookingCancelled;
use App\Notifications\GuestCancelledBooking;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReservationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        foreach (['super_admin', 'admin', 'staff', 'customer'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeUser(string $role): User
    {
        $user = User::create([
            'name' => ucfirst($role).' User',
            'email' => $role.uniqid().'@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);
        $user->assignRole($role);

        if ($role === 'staff') {
            $this->staffWorkEverywhere();
        }

        return $user;
    }

    private function makeReservation(User $guest, array $attrs = []): Reservation
    {
        $property = Property::create([
            'title' => 'Test Apartment '.uniqid(),
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
            'guests' => 1,
            'subtotal' => 20000,
            'total_amount' => 21500,
            'payment_status' => 'paid',
            'booking_status' => 'confirmed',
        ], $attrs));
    }

    public function test_staff_check_in_records_who_and_when(): void
    {
        $staff = $this->makeUser('staff');
        $reservation = $this->makeReservation($this->makeUser('customer'));

        $this->actingAs($staff)->patch(route('staff.checkin.process', $reservation))
            ->assertSessionHas('success');

        $reservation->refresh();
        $this->assertSame('checked_in', $reservation->booking_status);
        $this->assertSame($staff->id, $reservation->checked_in_by);
        $this->assertNotNull($reservation->checked_in_at);
    }

    public function test_mapped_legacy_room_completes_without_a_second_room_choice(): void
    {
        $admin = $this->makeUser('admin');
        $reservation = $this->makeReservation($this->makeUser('customer'), ['payment_status' => 'paid']);
        $roomType = RoomType::create([
            'name' => 'Standard',
            'slug' => 'standard',
            'base_price' => 10000,
            'total_rooms' => 1,
        ]);
        $room = Room::create([
            'room_type_id' => $roomType->id,
            'room_number' => '101',
            'status' => 'available',
            'unit_id' => $reservation->property_id,
        ]);
        $reservation->update(['room_id' => $room->id]);

        $this->actingAs($admin)
            ->patch(route('admin.reservations.checkin', $reservation))
            ->assertSessionHas('success');

        $reservation->refresh();
        $this->assertSame($room->id, $reservation->room_id);
        $this->assertSame('occupied', $room->fresh()->status);

        $this->actingAs($admin)
            ->patch(route('admin.reservations.checkout', $reservation))
            ->assertSessionHas('success');

        $this->assertSame('cleaning', $room->fresh()->status);
        $this->assertDatabaseHas('housekeeping_tasks', [
            'room_id' => $room->id,
            'task_type' => 'turnover',
            'status' => 'pending',
        ]);
    }

    public function test_cancelled_booking_cannot_be_checked_in(): void
    {
        $admin = $this->makeUser('admin');
        $reservation = $this->makeReservation($this->makeUser('customer'), ['booking_status' => 'cancelled']);

        $this->actingAs($admin)->patch(route('admin.reservations.checkin', $reservation))
            ->assertSessionHas('error');

        $this->assertSame('cancelled', $reservation->fresh()->booking_status);
    }

    public function test_guest_cannot_be_checked_out_before_checking_in(): void
    {
        $admin = $this->makeUser('admin');
        $reservation = $this->makeReservation($this->makeUser('customer'));

        $this->actingAs($admin)->patch(route('admin.reservations.checkout', $reservation))
            ->assertSessionHas('error');

        $this->assertSame('confirmed', $reservation->fresh()->booking_status);
    }

    public function test_checked_out_stay_cannot_be_cancelled(): void
    {
        $admin = $this->makeUser('admin');
        $reservation = $this->makeReservation($this->makeUser('customer'), ['booking_status' => 'checked_out']);

        $this->actingAs($admin)->patch(route('admin.reservations.cancel', $reservation))
            ->assertSessionHas('error');

        $this->assertSame('checked_out', $reservation->fresh()->booking_status);
    }

    public function test_check_in_is_refused_before_arrival_date(): void
    {
        $staff = $this->makeUser('staff');
        $reservation = $this->makeReservation($this->makeUser('customer'), [
            'check_in' => today()->addDays(3)->toDateString(),
            'check_out' => today()->addDays(5)->toDateString(),
        ]);

        $this->actingAs($staff)->patch(route('staff.checkin.process', $reservation))
            ->assertSessionHas('error');

        $this->assertSame('confirmed', $reservation->fresh()->booking_status);
    }

    public function test_check_in_is_refused_when_reserved_unit_is_under_maintenance(): void
    {
        $admin = $this->makeUser('admin');
        $reservation = $this->makeReservation($this->makeUser('customer'), ['payment_status' => 'paid']);
        $reservation->property->update(['status' => 'maintenance']);

        $this->actingAs($admin)->patch(route('admin.reservations.checkin', $reservation))
            ->assertSessionHas('error');

        $this->assertSame('confirmed', $reservation->fresh()->booking_status);
    }

    public function test_unpaid_guest_needs_admin_override_to_check_in(): void
    {
        $staff = $this->makeUser('staff');
        $admin = $this->makeUser('admin');
        $reservation = $this->makeReservation($this->makeUser('customer'), ['payment_status' => 'pending']);

        // Staff cannot, even if they send the override flag.
        $this->actingAs($staff)->patch(route('staff.checkin.process', $reservation), ['allow_unpaid' => 1])
            ->assertSessionHas('error');
        $this->assertSame('confirmed', $reservation->fresh()->booking_status);

        // Admin without the override is refused; with it, allowed.
        $this->actingAs($admin)->patch(route('admin.reservations.checkin', $reservation))
            ->assertSessionHas('error');
        $this->actingAs($admin)->patch(route('admin.reservations.checkin', $reservation), ['allow_unpaid' => 1])
            ->assertSessionHas('success');
        $this->assertSame('checked_in', $reservation->fresh()->booking_status);
    }

    public function test_partially_paid_guest_must_clear_balance_before_check_in(): void
    {
        $staff = $this->makeUser('staff');
        $reservation = $this->makeReservation($this->makeUser('customer'), ['payment_status' => 'partial']);

        $this->actingAs($staff)->patch(route('staff.checkin.process', $reservation))
            ->assertSessionHas('error');

        $this->assertSame('confirmed', $reservation->fresh()->booking_status);
    }

    public function test_admin_check_out_records_who_and_when(): void
    {
        $admin = $this->makeUser('admin');
        $reservation = $this->makeReservation($this->makeUser('customer'), ['booking_status' => 'checked_in']);

        $this->actingAs($admin)->patch(route('admin.reservations.checkout', $reservation))
            ->assertSessionHas('success');

        $reservation->refresh();
        $this->assertSame('checked_out', $reservation->booking_status);
        $this->assertSame($admin->id, $reservation->checked_out_by);
    }

    public function test_customer_cancellation_records_details_flags_refund_and_notifies(): void
    {
        $admin = $this->makeUser('admin');
        $guest = $this->makeUser('customer');
        $reservation = $this->makeReservation($guest, [
            'check_in' => today()->addDays(5)->toDateString(),
            'check_out' => today()->addDays(7)->toDateString(),
        ]);

        $this->actingAs($guest)->post(route('customer.reservations.cancel', $reservation), ['reason' => 'Change of plans'])
            ->assertRedirect(route('customer.reservations.show', $reservation));

        $reservation->refresh();
        $this->assertSame('cancelled', $reservation->booking_status);
        $this->assertSame($guest->id, $reservation->cancelled_by);
        $this->assertNotNull($reservation->cancelled_at);
        $this->assertSame('Change of plans', $reservation->cancellation_reason);
        $this->assertSame('pending', $reservation->refund_status);

        Notification::assertSentTo($guest, BookingCancelled::class);
        Notification::assertSentTo($admin, GuestCancelledBooking::class);
    }

    public function test_customer_cannot_cancel_on_or_after_check_in_date(): void
    {
        $guest = $this->makeUser('customer');
        $reservation = $this->makeReservation($guest);

        $this->actingAs($guest)->post(route('customer.reservations.cancel', $reservation))
            ->assertSessionHas('error');

        $this->assertSame('confirmed', $reservation->fresh()->booking_status);
    }

    public function test_customer_cannot_cancel_someone_elses_booking(): void
    {
        $reservation = $this->makeReservation($this->makeUser('customer'), [
            'check_in' => today()->addDays(5)->toDateString(),
            'check_out' => today()->addDays(7)->toDateString(),
        ]);

        $this->actingAs($this->makeUser('customer'))
            ->post(route('customer.reservations.cancel', $reservation))
            ->assertForbidden();
    }

    public function test_admin_records_refund_outcome(): void
    {
        $admin = $this->makeUser('admin');
        $reservation = $this->makeReservation($this->makeUser('customer'), [
            'booking_status' => 'cancelled',
            'refund_status' => 'pending',
            'processing_fee' => 2000,
            'caution_fee' => 20000,
            'total_amount' => 43500,
        ]);

        $this->actingAs($admin)->patch(route('admin.reservations.refund', $reservation), ['refund_status' => 'refunded'])
            ->assertSessionHas('success');

        $reservation->refresh();
        $this->assertSame('refunded', $reservation->refund_status);
        $this->assertSame('paid', $reservation->payment_status);
        $this->assertSame(21500.0, $reservation->booking_refundable_amount);
    }

    public function test_admin_reservation_page_shows_history_and_valid_actions_only(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');
        $reservation = $this->makeReservation($this->makeUser('customer'), [
            'booking_status' => 'checked_in',
            'checked_in_at' => now(),
            'checked_in_by' => $staff->id,
        ]);

        $this->actingAs($admin)->get(route('admin.reservations.show', $reservation))
            ->assertOk()
            ->assertSee('Check Out Guest')
            ->assertDontSee('Confirm Reservation')
            ->assertDontSee('Cancel Reservation')
            ->assertSee($staff->name);
    }

    public function test_staff_can_only_work_on_their_own_tasks(): void
    {
        $admin = $this->makeUser('admin');
        $mine = $this->makeUser('staff');
        $other = $this->makeUser('staff');
        $task = Task::create([
            'title' => 'Clean apartment',
            'assigned_to' => $other->id,
            'assigned_by' => $admin->id,
            'type' => 'cleaning',
            'priority' => 'medium',
            'status' => 'pending',
            'due_date' => today()->addDay(),
        ]);

        $this->actingAs($mine)->patch(route('staff.tasks.start', $task))->assertForbidden();
        $this->actingAs($mine)->patch(route('staff.tasks.complete', $task))->assertForbidden();
        $this->actingAs($mine)->get(route('staff.tasks.show', $task))->assertForbidden();
        $this->assertSame('pending', $task->fresh()->status);

        $this->actingAs($other)->get(route('staff.tasks.index'))->assertOk()->assertSee('Clean apartment');
        $this->actingAs($other)->get(route('staff.tasks.show', $task))->assertOk();
        $this->actingAs($other)->patch(route('staff.tasks.start', $task))->assertSessionHas('success');
        $this->actingAs($other)->post(route('staff.tasks.notes', $task), ['notes' => 'Bathroom needs supplies'])
            ->assertSessionHas('success');
        $this->assertSame('Bathroom needs supplies', $task->taskNotes()->first()->body);
    }

    public function test_customer_booking_page_offers_cancellation_with_policy(): void
    {
        $guest = $this->makeUser('customer');
        $reservation = $this->makeReservation($guest, [
            'check_in' => today()->addDays(5)->toDateString(),
            'check_out' => today()->addDays(7)->toDateString(),
        ]);
        $reservation->property->update(['cancellation_policy' => 'Free cancellation up to 48 hours before arrival.']);

        $this->actingAs($guest)->get(route('customer.reservations.show', $reservation))
            ->assertOk()
            ->assertSee('Cancel booking')
            ->assertSee('Free cancellation up to 48 hours before arrival.')
            ->assertSee($reservation->booking_ref);

        $this->actingAs($guest)->get(route('customer.reservations.index'))
            ->assertOk()
            ->assertSee('Cancel');
    }

    public function test_guest_can_review_a_finished_stay_once(): void
    {
        $guest = $this->makeUser('customer');
        $reservation = $this->makeReservation($guest, ['booking_status' => 'checked_out']);

        $this->actingAs($guest)->get(route('customer.reservations.show', $reservation))
            ->assertOk()->assertSee('Leave a Review');

        $this->actingAs($guest)->post(route('customer.reviews.store', $reservation), [
            'overall_rating' => 5,
            'comment' => 'Lovely stay',
        ])->assertSessionHasNoErrors();

        $this->assertSame(5, (int) $reservation->review()->first()->overall_rating);

        $this->actingAs($guest)->post(route('customer.reviews.store', $reservation), ['overall_rating' => 1])
            ->assertSessionHas('error');
        $this->assertSame(1, $reservation->review()->count());
    }

    public function test_staff_front_desk_pages_flag_unpaid_arrivals(): void
    {
        $staff = $this->makeUser('staff');
        $guest = $this->makeUser('customer');
        $unpaid = $this->makeReservation($guest, ['payment_status' => 'pending']);
        $this->makeReservation($guest, ['booking_status' => 'checked_in', 'check_out' => today()->toDateString(), 'check_in' => today()->subDay()->toDateString()]);

        $this->actingAs($staff)->get(route('staff.dashboard'))->assertOk()->assertSee('Unpaid: ask admin');
        $this->actingAs($staff)->get(route('staff.checkin.index'))->assertOk()->assertSee('Unpaid: ask admin');
        $this->actingAs($staff)->get(route('staff.checkin.search', ['search' => $unpaid->booking_ref]))
            ->assertOk()->assertSee($unpaid->booking_ref);
        $this->actingAs($staff)->get(route('staff.checkout.index'))->assertOk()->assertSee($guest->name);
    }

    public function test_header_dashboard_link_matches_role(): void
    {
        $this->actingAs($this->makeUser('customer'))->get('/')
            ->assertSee(route('customer.dashboard'), false)
            ->assertDontSee(route('admin.dashboard'), false);
    }
}
