<?php

namespace Tests\Feature;

use App\Events\ReservationStatusChanged;
use App\Models\BlockedDate;
use App\Models\GuestProfile;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\BookingConfirmed;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiAndAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.paystack.secret' => 'sk_test_secret']);
        Notification::fake();

        foreach (['super_admin', 'admin', 'staff', 'customer'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeUser(string $role, array $attrs = []): User
    {
        $user = User::create(array_merge([
            'name' => ucfirst($role).' User',
            'email' => $role.uniqid().'@example.com',
            'password' => 'password123',
            'status' => 'active',
        ], $attrs));
        $user->assignRole($role);

        return $user;
    }

    private function makeProperty(array $attrs = []): Property
    {
        return Property::create(array_merge([
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
        ], $attrs));
    }

    private function makeReservation(User $user, Property $property, string $in, string $out, array $attrs = []): Reservation
    {
        return Reservation::create(array_merge([
            'booking_ref' => Reservation::generateBookingRef(),
            'user_id' => $user->id,
            'property_id' => $property->id,
            'check_in' => $in,
            'check_out' => $out,
            'nights' => 1,
            'guests' => 1,
            'subtotal' => 10000,
            'tax_amount' => 750,
            'total_amount' => 10750,
            'payment_status' => 'pending',
            'booking_status' => 'confirmed',
        ], $attrs));
    }

    public function test_availability_check_uses_real_booking_data(): void
    {
        $property = $this->makeProperty(['title' => 'Ocean View']);
        $this->makeReservation($this->makeUser('customer'), $property, '2030-05-10', '2030-05-12');

        $this->getJson("/availability/check?property_id={$property->id}&check_in=2030-05-11&check_out=2030-05-13")
            ->assertOk()
            ->assertJson(['name' => 'Ocean View', 'available' => false, 'nights' => 2]);

        $this->getJson("/availability/check?property_id={$property->id}&check_in=2030-05-12&check_out=2030-05-14")
            ->assertOk()
            ->assertJson([
                'available' => true,
                'subtotal' => 20000,
                'processing_fee' => 2000,
                'caution_fee' => 20000,
                'total_price' => 43500,
            ]);

        $this->getJson('/availability/check?check_in=2030-05-12&check_out=2030-05-14')
            ->assertOk()
            ->assertJsonPath('properties.0.available', true);
    }

    public function test_calendar_marks_bookings_and_blocks_without_guest_names(): void
    {
        $property = $this->makeProperty();
        $start = now()->addDays(3)->startOfDay();

        $this->makeReservation($this->makeUser('customer', ['name' => 'Secret Guest']), $property,
            $start->toDateString(), $start->copy()->addDays(2)->toDateString());

        BlockedDate::create([
            'property_id' => $property->id,
            'start_date' => $start->copy()->addDays(5)->toDateString(),
            'end_date' => $start->copy()->addDays(6)->toDateString(),
            'reason' => 'Maintenance',
        ]);

        $response = $this->getJson("/availability/calendar/{$property->id}")->assertOk();
        $days = collect($response->json('calendar'))->keyBy('date');

        $this->assertSame('confirmed', $days[$start->toDateString()]['status']);
        $this->assertSame('confirmed', $days[$start->copy()->addDay()->toDateString()]['status']);
        $this->assertTrue($days[$start->copy()->addDays(2)->toDateString()]['available']);
        $this->assertSame('blocked', $days[$start->copy()->addDays(5)->toDateString()]['status']);
        $this->assertSame('blocked', $days[$start->copy()->addDays(6)->toDateString()]['status']);
        $this->assertTrue($days[$start->copy()->addDays(7)->toDateString()]['available']);
        $this->assertStringNotContainsString('Secret Guest', $response->getContent());
    }

    public function test_api_book_prices_server_side_and_records_payment(): void
    {
        $property = $this->makeProperty();
        $customer = $this->makeUser('customer');

        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://checkout.paystack.test/xyz'],
            ]),
        ]);

        $this->actingAs($customer, 'sanctum')->postJson("/api/properties/{$property->id}/book", [
            'check_in' => '2030-06-01',
            'check_out' => '2030-06-03',
            'guests' => 2,
            'total_price' => 1,
        ])->assertCreated()->assertJsonPath('authorization_url', 'https://checkout.paystack.test/xyz');

        $reservation = Reservation::firstOrFail();
        $this->assertSame($customer->id, $reservation->user_id);
        $this->assertSame('pending', $reservation->booking_status);
        $this->assertEquals(20000, (float) $reservation->subtotal);
        $this->assertGreaterThan(20000, (float) $reservation->total_amount);
        $this->assertSame(1, Payment::where('paystack_reference', $reservation->booking_ref)->count());

        $this->actingAs($customer, 'sanctum')->postJson("/api/properties/{$property->id}/book", [
            'check_in' => '2030-06-02',
            'check_out' => '2030-06-04',
            'guests' => 1,
        ])->assertStatus(422)->assertJson(['message' => 'Selected dates are already booked']);
    }

    public function test_api_availability_returns_pricing(): void
    {
        $property = $this->makeProperty();

        $this->actingAs($this->makeUser('customer'), 'sanctum')
            ->getJson("/api/properties/{$property->id}/availability?check_in=2030-07-01&check_out=2030-07-04")
            ->assertOk()
            ->assertJson(['available' => true, 'nights' => 3, 'subtotal' => 30000]);
    }

    public function test_api_admin_dashboard_and_reservation_update(): void
    {
        $admin = $this->makeUser('admin');
        $property = $this->makeProperty();
        $customer = $this->makeUser('customer');
        $pending = $this->makeReservation($customer, $property, '2030-08-01', '2030-08-02', ['booking_status' => 'pending']);
        $this->makeReservation($customer, $property, '2030-08-05', '2030-08-06');

        $this->actingAs($admin, 'sanctum')->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJson(['pending_reservations' => 1, 'confirmed_reservations' => 1]);

        $this->actingAs($admin, 'sanctum')->getJson('/api/admin/reservations?status=pending')
            ->assertOk()
            ->assertJsonPath('total', 1);

        Event::fake([ReservationStatusChanged::class]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/reservations/{$pending->id}", ['status' => 'cancelled', 'reason' => 'Guest asked'])
            ->assertOk();

        $pending->refresh();
        $this->assertSame('cancelled', $pending->booking_status);
        $this->assertSame($admin->id, $pending->cancelled_by);
        $this->assertSame('Guest asked', $pending->cancellation_reason);
        Event::assertDispatchedTimes(ReservationStatusChanged::class, 1);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/reservations/{$pending->id}", ['status' => 'completed'])
            ->assertStatus(422);
    }

    public function test_api_admin_property_crud_validates_real_columns(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin, 'sanctum')->postJson('/api/admin/properties', [
            'name' => 'Old field', 'location' => 'Lagos',
        ])->assertStatus(422)->assertJsonValidationErrors(['title', 'address', 'city', 'toilets']);

        $id = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/properties', [
            'title' => 'API Flat',
            'description' => 'Created over the API',
            'address' => '2 API Road',
            'city' => 'Abuja',
            'state' => 'FCT',
            'price_per_night' => 25000,
            'max_guests' => 3,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'toilets' => 1,
        ])->assertCreated()->json('property.id');

        $property = Property::findOrFail($id);
        $this->assertSame('api-flat', $property->slug);
        $this->assertSame($admin->id, $property->created_by);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/properties/{$id}", ['price_per_night' => 30000, 'created_by' => 999])
            ->assertOk();

        $property->refresh();
        $this->assertEquals(30000, (float) $property->price_per_night);
        $this->assertSame($admin->id, $property->created_by);
    }

    public function test_api_admin_customer_search_stays_scoped_to_customers(): void
    {
        $admin = $this->makeUser('admin', ['name' => 'Ada Admin']);
        $this->makeUser('customer', ['name' => 'Ada Customer']);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/customers?search=Ada')->assertOk();

        $this->assertSame(['Ada Customer'], collect($response->json('data'))->pluck('name')->all());
    }

    public function test_invoice_uses_reservation_amounts_and_successful_payments(): void
    {
        $admin = $this->makeUser('admin');
        $reservation = $this->makeReservation($this->makeUser('customer'), $this->makeProperty(), '2030-09-01', '2030-09-02', [
            'payment_status' => 'paid',
        ]);
        Payment::create([
            'reservation_id' => $reservation->id,
            'user_id' => $reservation->user_id,
            'amount' => 10750,
            'paystack_reference' => 'ref-ok',
            'paystack_status' => 'success',
            'type' => 'payment',
        ]);
        Payment::create([
            'reservation_id' => $reservation->id,
            'user_id' => $reservation->user_id,
            'amount' => 5000,
            'paystack_reference' => 'ref-abandoned',
            'type' => 'payment',
        ]);

        $this->actingAs($admin)->post('/admin/invoices', ['reservation_id' => $reservation->id])->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'reservation_id' => $reservation->id,
            'subtotal' => 10000,
            'vat_amount' => 750,
            'total_amount' => 10750,
            'paid_amount' => 10750,
            'balance' => 0,
        ]);
    }

    public function test_loyalty_points_cannot_be_applied_to_someone_elses_reservation(): void
    {
        $owner = $this->makeUser('customer');
        $attacker = $this->makeUser('customer');
        $profile = GuestProfile::create(['user_id' => $attacker->id, 'loyalty_points' => 1000]);
        $reservation = $this->makeReservation($owner, $this->makeProperty(), '2030-10-01', '2030-10-02');

        $this->actingAs($attacker)->post("/customer/loyalty/{$reservation->id}/apply", ['points' => 500])->assertNotFound();

        $this->assertSame(1000, $profile->refresh()->loyalty_points);
        $this->assertEquals(10750, (float) $reservation->refresh()->total_amount);
    }

    public function test_loyalty_points_reduce_own_unpaid_reservation_total(): void
    {
        $owner = $this->makeUser('customer');
        $profile = GuestProfile::create(['user_id' => $owner->id, 'loyalty_points' => 1000]);
        $reservation = $this->makeReservation($owner, $this->makeProperty(), '2030-10-01', '2030-10-02');

        $this->actingAs($owner)->post("/customer/loyalty/{$reservation->id}/apply", ['points' => 500])->assertRedirect();

        $this->assertSame(500, $profile->refresh()->loyalty_points);
        $this->assertEquals(10745, (float) $reservation->refresh()->total_amount);
    }

    public function test_admin_confirm_sends_one_notification(): void
    {
        $customer = $this->makeUser('customer');
        $reservation = $this->makeReservation($customer, $this->makeProperty(), '2030-11-01', '2030-11-02', ['booking_status' => 'pending']);

        $this->actingAs($this->makeUser('admin'))
            ->patch("/admin/reservations/{$reservation->id}/confirm")
            ->assertRedirect();

        Notification::assertSentToTimes($customer, BookingConfirmed::class, 1);
    }

    public function test_property_name_falls_back_to_title(): void
    {
        $this->assertSame('Garden Suite', $this->makeProperty(['title' => 'Garden Suite'])->name);
    }

    public function test_admin_can_export_property_reservations_as_ical(): void
    {
        $admin = $this->makeUser('admin');
        $property = $this->makeProperty(['title' => 'Calendar Apartment']);
        $customer = $this->makeUser('customer', ['name' => 'Calendar Guest']);
        $this->makeReservation($customer, $property, '2030-12-10', '2030-12-12');
        $this->makeReservation($customer, $property, '2030-12-20', '2030-12-22', ['booking_status' => 'cancelled']);

        $response = $this->actingAs($admin)
            ->get("/admin/ical/property/{$property->id}")
            ->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=UTF-8');

        $ical = $response->getContent();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $ical);
        $this->assertStringContainsString('Calendar Apartment - Calendar Guest', $ical);
        $this->assertStringContainsString('DTSTART;VALUE=DATE:20301210', $ical);
        $this->assertStringContainsString('END:VCALENDAR', $ical);
        $this->assertStringNotContainsString('20301220', $ical);
    }

    public function test_admin_can_get_calendar_feed_urls(): void
    {
        $admin = $this->makeUser('admin');
        $property = $this->makeProperty();

        $this->actingAs($admin)
            ->getJson("/admin/ical/url?property_id={$property->id}")
            ->assertOk()
            ->assertJsonPath('url', route('admin.ical.property', $property->id))
            ->assertJsonStructure(['url', 'instructions']);
    }
}
