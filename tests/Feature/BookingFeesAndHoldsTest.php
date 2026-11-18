<?php

namespace Tests\Feature;

use App\Events\ReservationStatusChanged;
use App\Models\GuestProfile;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\User;
use App\Services\BookingService;
use App\Services\PaymentService;
use App\Services\PricingService;
use App\Services\ReservationLifecycle;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BookingFeesAndHoldsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'booking.processing_fee' => 2000,
            'booking.caution_fee' => 20000,
            'booking.hold_minutes' => 15,
            'services.paystack.secret' => 'sk_test_secret',
        ]);

        Notification::fake();

        foreach (['super_admin', 'admin', 'customer'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_pricing_adds_processing_and_refundable_caution_fees(): void
    {
        $pricing = app(PricingService::class)->calculatePrice(
            $this->makeProperty(),
            '2030-01-01',
            '2030-01-03',
            2,
        );

        $this->assertSame(20000.0, (float) $pricing['subtotal']);
        $this->assertSame(1500.0, (float) $pricing['tax_amount']);
        $this->assertSame(2000.0, (float) $pricing['processing_fee']);
        $this->assertSame(20000.0, (float) $pricing['caution_fee']);
        $this->assertSame(43500.0, (float) $pricing['total_amount']);
    }

    public function test_new_booking_records_fees_and_an_expiring_hold(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'reference' => 'paystack-reference',
                    'authorization_url' => 'https://checkout.paystack.test/hold',
                ],
            ]),
        ]);

        [$reservation] = app(BookingService::class)->startBooking(
            $this->makeUser('customer'),
            $this->makeProperty(),
            [
                'check_in' => '2030-02-01',
                'check_out' => '2030-02-03',
                'guests' => 1,
            ],
        );

        $this->assertSame(2000.0, (float) $reservation->processing_fee);
        $this->assertSame(20000.0, (float) $reservation->caution_fee);
        $this->assertSame('pending', $reservation->caution_fee_status);
        $this->assertNotNull($reservation->hold_expires_at);
        $this->assertTrue($reservation->hold_expires_at->between(now()->addMinutes(14), now()->addMinutes(16)));
    }

    public function test_expired_pending_hold_does_not_block_availability(): void
    {
        $property = $this->makeProperty();
        $customer = $this->makeUser('customer');

        $this->makeReservation($customer, $property, [
            'check_in' => '2030-03-01',
            'check_out' => '2030-03-03',
            'hold_expires_at' => now()->subMinute(),
        ]);

        $this->assertTrue(
            app(PricingService::class)->checkAvailability($property, '2030-03-01', '2030-03-03'),
        );

        $this->makeReservation($customer, $property, [
            'check_in' => '2030-03-05',
            'check_out' => '2030-03-07',
            'hold_expires_at' => now()->addMinutes(10),
        ]);

        $this->expectExceptionMessage('Selected dates are already booked');
        app(PricingService::class)->checkAvailability($property, '2030-03-05', '2030-03-07');
    }

    public function test_expire_holds_command_cancels_only_stale_unpaid_bookings(): void
    {
        $property = $this->makeProperty();
        $customer = $this->makeUser('customer');
        $expired = $this->makeReservation($customer, $property, [
            'hold_expires_at' => now()->subMinute(),
        ]);
        $active = $this->makeReservation($customer, $property, [
            'check_in' => '2030-05-01',
            'check_out' => '2030-05-03',
            'hold_expires_at' => now()->addMinutes(10),
        ]);

        $this->artisan('bookings:expire-holds')->assertSuccessful();

        $this->assertSame('cancelled', $expired->refresh()->booking_status);
        $this->assertSame('Payment window expired', $expired->cancellation_reason);
        $this->assertSame('pending', $active->refresh()->booking_status);
    }

    public function test_payment_confirmation_holds_caution_fee_and_dispatches_lifecycle_event(): void
    {
        Event::fake([ReservationStatusChanged::class]);

        $reservation = $this->makeReservation(
            $this->makeUser('customer'),
            $this->makeProperty(),
            ['total_amount' => 32750],
        );

        app(PaymentService::class)->markPaymentSuccessful([
            'status' => 'success',
            'reference' => $reservation->booking_ref,
            'amount' => 3275000,
            'currency' => 'NGN',
            'channel' => 'card',
        ]);

        $reservation->refresh();
        $this->assertSame('confirmed', $reservation->booking_status);
        $this->assertSame('paid', $reservation->payment_status);
        $this->assertSame('held', $reservation->caution_fee_status);
        $this->assertNull($reservation->hold_expires_at);

        Event::assertDispatched(ReservationStatusChanged::class, fn ($event) => $event->reservation->is($reservation)
            && $event->oldStatus === 'pending'
            && $event->newStatus === 'confirmed'
            && $event->actorId === null
        );
    }

    public function test_late_payment_for_expired_booking_is_queued_for_refund(): void
    {
        $reservation = $this->makeReservation(
            $this->makeUser('customer'),
            $this->makeProperty(),
            [
                'booking_status' => 'cancelled',
                'cancellation_reason' => 'Payment window expired',
                'hold_expires_at' => null,
            ],
        );

        app(PaymentService::class)->markPaymentSuccessful([
            'status' => 'success',
            'reference' => $reservation->booking_ref,
            'amount' => 3275000,
            'currency' => 'NGN',
            'channel' => 'card',
        ]);

        $reservation->refresh();
        $this->assertSame('cancelled', $reservation->booking_status);
        $this->assertSame('paid', $reservation->payment_status);
        $this->assertSame('pending', $reservation->refund_status);
        $this->assertSame('refund_pending', $reservation->caution_fee_status);
    }

    public function test_checkout_marks_caution_fee_for_refund(): void
    {
        Event::fake([ReservationStatusChanged::class]);

        $admin = $this->makeUser('admin');
        $reservation = $this->makeReservation(
            $this->makeUser('customer'),
            $this->makeProperty(),
            [
                'booking_status' => 'checked_in',
                'payment_status' => 'paid',
                'caution_fee_status' => 'held',
                'check_in' => now()->subDay()->toDateString(),
                'check_out' => today()->toDateString(),
            ],
        );

        app(ReservationLifecycle::class)->checkOut($reservation, $admin);

        $this->assertSame('refund_pending', $reservation->refresh()->caution_fee_status);
    }

    public function test_admin_can_record_caution_fee_refund(): void
    {
        $reservation = $this->makeReservation(
            $this->makeUser('customer'),
            $this->makeProperty(),
            ['caution_fee_status' => 'refund_pending'],
        );

        $this->actingAs($this->makeUser('admin'))
            ->patch(route('admin.reservations.caution-refund', $reservation), [
                'caution_fee_status' => 'refunded',
            ])
            ->assertRedirect();

        $reservation->refresh();
        $this->assertSame('refunded', $reservation->caution_fee_status);
        $this->assertNotNull($reservation->caution_refunded_at);
        $this->assertSame('pending', $reservation->payment_status);
    }

    public function test_refundable_caution_fee_is_not_reported_as_revenue(): void
    {
        $reservation = $this->makeReservation(
            $this->makeUser('customer'),
            $this->makeProperty(),
            [
                'payment_status' => 'paid',
                'booking_status' => 'confirmed',
                'total_amount' => 32750,
                'caution_fee' => 20000,
            ],
        );

        $this->actingAs($this->makeUser('admin'), 'sanctum')
            ->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('total_revenue', 12750);

        $this->assertSame(12750.0, $reservation->revenue_amount);
    }

    public function test_loyalty_discount_cannot_consume_booking_fees(): void
    {
        $customer = $this->makeUser('customer');
        $profile = GuestProfile::create([
            'user_id' => $customer->id,
            'loyalty_points' => 1500000,
        ]);
        $reservation = $this->makeReservation($customer, $this->makeProperty());

        $this->actingAs($customer)
            ->post("/customer/loyalty/{$reservation->id}/apply", ['points' => 1500000])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(1500000, $profile->refresh()->loyalty_points);
        $this->assertSame(32750.0, (float) $reservation->refresh()->total_amount);
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

        return $user;
    }

    private function makeProperty(): Property
    {
        return Property::create([
            'title' => 'Test Apartment '.uniqid(),
            'description' => 'Nice place',
            'address' => '1 Test Street',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'price_per_night' => 10000,
            'max_guests' => 4,
            'min_nights' => 1,
            'max_nights' => 30,
            'bedrooms' => 2,
            'bathrooms' => 2,
            'toilets' => 2,
            'status' => 'active',
        ]);
    }

    private function makeReservation(User $user, Property $property, array $attributes = []): Reservation
    {
        return Reservation::create(array_merge([
            'booking_ref' => Reservation::generateBookingRef(),
            'user_id' => $user->id,
            'property_id' => $property->id,
            'check_in' => '2030-04-01',
            'check_out' => '2030-04-03',
            'nights' => 2,
            'guests' => 1,
            'subtotal' => 10000,
            'discount_amount' => 0,
            'tax_amount' => 750,
            'processing_fee' => 2000,
            'caution_fee' => 20000,
            'caution_fee_status' => 'pending',
            'total_amount' => 32750,
            'payment_status' => 'pending',
            'booking_status' => 'pending',
            'hold_expires_at' => now()->addMinutes(15),
        ], $attributes));
    }
}
