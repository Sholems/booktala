<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Position;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\Staff;
use App\Models\User;
use App\Services\BookingService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalkInBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_receptionist_records_a_cash_walk_in_without_a_user_account(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $site = Site::create(['name' => 'Lekki', 'address' => '1 Road', 'city' => 'Lagos', 'state' => 'Lagos']);
        $unit = $this->unitAt($site);
        $receptionist = $this->receptionistAt($site);

        $this->actingAs($receptionist)->get(route('frontdesk.reservations.create'))
            ->assertOk()
            ->assertSee('New front-desk booking');

        $this->actingAs($receptionist)->from(route('frontdesk.reservations.create'))->post(route('frontdesk.reservations.store'), [
            'site_id' => $site->id,
            'unit_id' => $unit->id,
            'guest_name' => 'Walk In Guest',
            'guest_phone' => '08031234567',
            'check_in' => today()->toDateString(),
            'check_out' => today()->addDays(2)->toDateString(),
            'guests' => 2,
            'payment_method' => 'cash',
            'amount' => 50000,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('reservations', [
            'property_id' => $unit->id,
            'booking_source' => 'walk_in',
            'created_by' => $receptionist->id,
            'user_id' => null,
        ]);
        $this->assertDatabaseHas('payments', [
            'provider' => 'offline',
            'payment_method' => 'cash',
            'collected_by' => $receptionist->id,
            'paystack_status' => 'success',
        ]);
        $reservation = Reservation::firstOrFail();
        $this->assertSame('40000.00', $reservation->subtotal);
        $this->assertSame('2000.00', $reservation->processing_fee);
        $this->assertSame('20000.00', $reservation->caution_fee);
        $this->assertSame('65000.00', $reservation->total_amount);
        $this->assertSame('partial', $reservation->payment_status);
        $invoice = Invoice::with('items')->where('reservation_id', $reservation->id)->firstOrFail();
        $this->assertSame('65000.00', $invoice->total_amount);
        $this->assertSame('50000.00', $invoice->paid_amount);
        $this->assertSame('15000.00', $invoice->balance);
        $this->assertEqualsWithDelta(65000, $invoice->items->sum('total'), 0.01);
        $this->actingAs($receptionist)->get(route('frontdesk.reservations.receipt', $reservation))
            ->assertOk()
            ->assertSee($reservation->booking_ref)
            ->assertSee('Refundable caution fee');
    }

    public function test_receptionist_cannot_submit_a_unit_outside_their_site(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $lekki = Site::create(['name' => 'Lekki', 'address' => '1 Road', 'city' => 'Lagos', 'state' => 'Lagos']);
        $abuja = Site::create(['name' => 'Abuja', 'address' => '2 Road', 'city' => 'Abuja', 'state' => 'FCT']);
        $receptionist = $this->receptionistAt($lekki);
        $unit = $this->unitAt($abuja);

        $this->actingAs($receptionist)->post(route('frontdesk.reservations.store'), [
            'site_id' => $abuja->id, 'unit_id' => $unit->id, 'guest_name' => 'Blocked Guest', 'guest_phone' => '08031234567',
            'check_in' => today()->toDateString(), 'check_out' => today()->addDay()->toDateString(), 'guests' => 1,
            'payment_method' => 'cash', 'amount' => 10000,
        ])->assertNotFound();
    }

    public function test_receptionist_can_quote_only_units_at_their_site(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $lekki = Site::create(['name' => 'Lekki', 'address' => '1 Road', 'city' => 'Lagos', 'state' => 'Lagos']);
        $abuja = Site::create(['name' => 'Abuja', 'address' => '2 Road', 'city' => 'Abuja', 'state' => 'FCT']);
        $receptionist = $this->receptionistAt($lekki);
        $unit = $this->unitAt($lekki);
        $otherUnit = $this->unitAt($abuja);

        $this->actingAs($receptionist)->postJson(route('frontdesk.reservations.quote'), [
            'site_id' => $lekki->id, 'unit_id' => $unit->id, 'check_in' => today()->toDateString(),
            'check_out' => today()->addDays(2)->toDateString(), 'guests' => 2,
        ])->assertOk()->assertJsonPath('total_amount', 65000);

        $this->actingAs($receptionist)->postJson(route('frontdesk.reservations.quote'), [
            'site_id' => $abuja->id, 'unit_id' => $otherUnit->id, 'check_in' => today()->toDateString(),
            'check_out' => today()->addDay()->toDateString(), 'guests' => 1,
        ])->assertNotFound();
    }

    public function test_duplicate_offline_reference_rolls_back_the_second_booking(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $site = Site::create(['name' => 'Lekki', 'address' => '1 Road', 'city' => 'Lagos', 'state' => 'Lagos']);
        $unit = $this->unitAt($site);
        $receptionist = $this->receptionistAt($site);
        $payload = $this->payload($site, $unit, [
            'payment_method' => 'transfer',
            'reference' => 'BANK-123',
            'amount' => 10000,
        ]);

        $this->actingAs($receptionist)->post(route('frontdesk.reservations.store'), $payload)->assertSessionHasNoErrors();
        $this->actingAs($receptionist)->from(route('frontdesk.reservations.create'))->post(
            route('frontdesk.reservations.store'),
            $payload + ['guest_name' => 'Second Guest', 'guest_phone' => '08099999999'],
        )->assertSessionHasErrors('booking');

        $this->assertSame(1, Reservation::count());
        $this->assertSame(1, Payment::count());
        $this->assertSame(1, Invoice::count());
    }

    public function test_matching_phone_reuses_guest_instead_of_creating_duplicate(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $site = Site::create(['name' => 'Lekki', 'address' => '1 Road', 'city' => 'Lagos', 'state' => 'Lagos']);
        $unit = $this->unitAt($site);
        $receptionist = $this->receptionistAt($site);
        $guest = Guest::create(['name' => 'Known Guest', 'phone' => '+234 803 123 4567']);
        app(BookingService::class)->createReservationForGuest($guest, $unit, [
            'check_in' => today()->addDays(5)->toDateString(),
            'check_out' => today()->addDays(6)->toDateString(),
            'guests' => 1,
        ], 'phone');

        $this->actingAs($receptionist)->post(route('frontdesk.reservations.store'), $this->payload($site, $unit, [
            'guest_name' => 'Known Guest',
            'guest_phone' => '2348031234567',
        ]))->assertSessionHasNoErrors();

        $this->assertSame(1, Guest::count());
        $this->assertSame($guest->id, Reservation::where('booking_source', 'walk_in')->firstOrFail()->guest_id);
    }

    public function test_guest_search_does_not_expose_guests_from_another_site(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $lekki = Site::create(['name' => 'Lekki', 'address' => '1 Road', 'city' => 'Lagos', 'state' => 'Lagos']);
        $abuja = Site::create(['name' => 'Abuja', 'address' => '2 Road', 'city' => 'Abuja', 'state' => 'FCT']);
        $receptionist = $this->receptionistAt($lekki);
        $guest = Guest::create(['name' => 'Abuja Guest', 'phone' => '08035550000']);
        app(BookingService::class)->createReservationForGuest($guest, $this->unitAt($abuja), [
            'check_in' => today()->addDays(5)->toDateString(),
            'check_out' => today()->addDays(6)->toDateString(),
            'guests' => 1,
        ], 'phone');

        $this->actingAs($receptionist)->getJson(route('frontdesk.guests.index', ['phone' => '08035550000']))
            ->assertOk()
            ->assertExactJson(['guests' => []]);
    }

    public function test_admin_can_take_full_payment_and_check_in_today_immediately(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $site = Site::create(['name' => 'Lekki', 'address' => '1 Road', 'city' => 'Lagos', 'state' => 'Lagos']);
        $unit = $this->unitAt($site);
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('frontdesk.reservations.store'), $this->payload($site, $unit, [
            'booking_source' => 'phone',
            'amount' => 43500,
            'check_in_now' => true,
        ]))->assertSessionHasNoErrors();

        $reservation = Reservation::firstOrFail();
        $this->assertSame('phone', $reservation->booking_source);
        $this->assertSame('paid', $reservation->payment_status);
        $this->assertSame('checked_in', $reservation->booking_status);
        $this->assertSame($admin->id, $reservation->checked_in_by);
    }

    public function test_receptionist_cannot_request_immediate_check_in_without_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $site = Site::create(['name' => 'Lekki', 'address' => '1 Road', 'city' => 'Lagos', 'state' => 'Lagos']);
        $unit = $this->unitAt($site);
        $receptionist = $this->receptionistAt($site);

        $this->actingAs($receptionist)->from(route('frontdesk.reservations.create'))->post(
            route('frontdesk.reservations.store'),
            $this->payload($site, $unit, ['amount' => 43500, 'check_in_now' => true]),
        )->assertSessionHasErrors('booking');

        $this->assertSame(0, Reservation::count());
        $this->assertSame(0, Payment::count());
    }

    private function receptionistAt(Site $site): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('staff');
        $staff = Staff::create(['user_id' => $user->id, 'employee_id' => 'R-'.$user->id, 'hire_date' => today()]);
        $staff->assignTo($site, Position::where('name', 'Receptionist')->firstOrFail());

        return $user;
    }

    private function unitAt(Site $site): Property
    {
        return Property::create([
            'title' => $site->name.' Apartment', 'description' => 'Test apartment', 'address' => $site->address,
            'city' => $site->city, 'state' => $site->state, 'price_per_night' => 20000, 'max_guests' => 4,
            'bedrooms' => 2, 'bathrooms' => 2, 'toilets' => 2, 'status' => 'active', 'site_id' => $site->id,
        ]);
    }

    private function payload(Site $site, Property $unit, array $overrides = []): array
    {
        return array_merge([
            'site_id' => $site->id,
            'unit_id' => $unit->id,
            'guest_name' => 'Walk In Guest',
            'guest_phone' => '08031234567',
            'check_in' => today()->toDateString(),
            'check_out' => today()->addDay()->toDateString(),
            'guests' => 1,
            'payment_method' => 'cash',
            'amount' => 10000,
        ], $overrides);
    }
}
