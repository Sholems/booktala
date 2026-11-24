<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\PricingService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BookingSecurityTest extends TestCase
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
            'bedrooms' => 2,
            'bathrooms' => 2,
            'toilets' => 2,
            'status' => 'active',
        ]);
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
            'total_amount' => 10750,
            'payment_status' => 'pending',
            'booking_status' => 'pending',
        ], $attrs));
    }

    public function test_customer_cannot_access_admin_routes(): void
    {
        $customer = $this->makeUser('customer');

        $this->actingAs($customer)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($customer)->get('/admin/customers')->assertForbidden();
        $this->actingAs($customer)->post('/admin/staff', [])->assertForbidden();
    }

    public function test_staff_cannot_access_admin_routes(): void
    {
        $this->actingAs($this->makeUser('staff'))->get('/admin/reservations')->assertForbidden();
    }

    public function test_admin_passes_admin_role_check(): void
    {
        $response = $this->actingAs($this->makeUser('admin'))->get('/admin/payments');

        $this->assertNotEquals(403, $response->status());
    }

    public function test_customer_cannot_use_admin_api(): void
    {
        $this->actingAs($this->makeUser('customer'), 'sanctum')
            ->getJson('/api/admin/customers')
            ->assertForbidden();
    }

    public function test_guest_cannot_book_as_existing_user_without_password(): void
    {
        $victim = $this->makeUser('customer', ['email' => 'victim@example.com']);
        $property = $this->makeProperty();

        $this->postJson('/booking/process', [
            'property_id' => $property->id,
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(7)->toDateString(),
            'guests' => 1,
            'guest_name' => 'Attacker',
            'guest_email' => 'victim@example.com',
            'guest_phone' => '0800',
            'guest_password' => 'wrong-password',
        ])->assertStatus(422);

        $this->assertGuest();
        $this->assertSame(0, Reservation::where('user_id', $victim->id)->count());
    }

    public function test_guest_cannot_skip_guest_fields_with_user_id(): void
    {
        $victim = $this->makeUser('customer');
        $property = $this->makeProperty();

        $this->postJson('/booking/process', [
            'property_id' => $property->id,
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(7)->toDateString(),
            'guests' => 1,
            'user_id' => $victim->id,
        ])->assertStatus(422)->assertJsonValidationErrors(['guest_email', 'guest_password']);

        $this->assertSame(0, Reservation::count());
    }

    public function test_booking_records_payment_and_callback_confirms(): void
    {
        $property = $this->makeProperty();

        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://checkout.paystack.test/abc'],
            ]),
        ]);

        $this->postJson('/booking/process', [
            'property_id' => $property->id,
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(7)->toDateString(),
            'guests' => 2,
            'policy_agreement' => true,
            'guest_name' => 'New Guest',
            'guest_email' => 'newguest@example.com',
            'guest_phone' => '0800',
            'guest_password' => 'secret123',
        ])->assertOk()->assertJson(['success' => true]);

        $reservation = Reservation::firstOrFail();
        $this->assertAuthenticatedAs($reservation->user);
        $this->assertDatabaseHas('payments', ['paystack_reference' => $reservation->booking_ref]);

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'reference' => $reservation->booking_ref,
                    'amount' => (int) round($reservation->total_amount * 100),
                    'currency' => 'NGN',
                    'channel' => 'card',
                ],
            ]),
        ]);

        $this->get('/booking/callback?reference='.$reservation->booking_ref)
            ->assertRedirect(route('booking.confirmation', $reservation->booking_ref));

        $reservation->refresh();
        $this->assertSame('paid', $reservation->payment_status);
        $this->assertSame('confirmed', $reservation->booking_status);
        $this->assertSame('success', Payment::firstOrFail()->paystack_status);
    }

    public function test_partial_payment_can_pay_half_then_the_remaining_balance(): void
    {
        $property = $this->makeProperty();

        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.test/partial',
                ],
            ]),
        ]);

        $this->postJson('/booking/process', [
            'property_id' => $property->id,
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(7)->toDateString(),
            'guests' => 2,
            'payment_mode' => 'partial',
            'policy_agreement' => true,
            'guest_name' => 'Partial Guest',
            'guest_email' => 'partial@example.com',
            'guest_phone' => '0800',
            'guest_password' => 'secret123',
        ])->assertOk()->assertJson(['success' => true]);

        $reservation = Reservation::firstOrFail();
        $partialAmount = (int) round($reservation->total_amount * 0.5 * 100);

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'reference' => $reservation->booking_ref,
                    'amount' => $partialAmount,
                    'currency' => 'NGN',
                    'channel' => 'card',
                ],
            ]),
        ]);

        $this->get('/booking/callback?reference='.$reservation->booking_ref)
            ->assertRedirect(route('booking.confirmation', $reservation->booking_ref));

        $this->assertSame('partial', $reservation->refresh()->payment_status);
        $this->assertSame('confirmed', $reservation->booking_status);
        $this->assertSame('pending', $reservation->caution_fee_status);

        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.test/balance',
                ],
            ]),
        ]);

        $this->actingAs($reservation->user)
            ->post(route('customer.reservations.pay-balance', $reservation))
            ->assertRedirect();

        $balancePayment = Payment::where('reservation_id', $reservation->id)
            ->where('paystack_reference', '!=', $reservation->booking_ref)
            ->firstOrFail();

        app(PaymentService::class)->markPaymentSuccessful([
            'status' => 'success',
            'reference' => $balancePayment->paystack_reference,
            'amount' => (int) round((float) $balancePayment->amount * 100),
            'currency' => 'NGN',
            'channel' => 'card',
        ]);

        $reservation->refresh();
        $this->assertSame('paid', $reservation->payment_status);
        $this->assertSame('held', $reservation->caution_fee_status);
    }

    public function test_booking_page_loads_without_paystack_credentials(): void
    {
        config(['services.paystack.secret' => null]);
        $property = $this->makeProperty();

        $this->get('/booking/'.$property->slug.'?check_in='
            .now()->addDays(5)->toDateString()
            .'&check_out='.now()->addDays(7)->toDateString()
            .'&guests=2')
            ->assertOk()
            ->assertSee('Refundable caution fee')
            ->assertSee('Proceed to Payment');
    }

    public function test_booking_fails_without_policy_agreement(): void
    {
        $property = $this->makeProperty();

        $this->postJson('/booking/process', [
            'property_id' => $property->id,
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(7)->toDateString(),
            'guests' => 2,
            'guest_name' => 'Test Guest',
            'guest_email' => 'testguest@example.com',
            'guest_phone' => '0800',
            'guest_password' => 'secret123',
            // Omit policy_agreement
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['policy_agreement']);
    }

    public function test_booking_records_policy_agreement_audit(): void
    {
        $property = $this->makeProperty();

        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://checkout.paystack.test/policy-test'],
            ]),
        ]);

        $this->postJson('/booking/process', [
            'property_id' => $property->id,
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(7)->toDateString(),
            'guests' => 2,
            'policy_agreement' => true,
            'guest_name' => 'Policy Tester',
            'guest_email' => 'policytester@example.com',
            'guest_phone' => '0800112233',
            'guest_password' => 'secret123',
        ])->assertOk();

        $reservation = Reservation::where('user_id', User::where('email', 'policytester@example.com')->value('id'))->firstOrFail();
        $this->assertNotNull($reservation->policy_agreed_at);
        $this->assertNotNull($reservation->policy_agreed_ip);
    }

    public function test_underpaid_callback_does_not_confirm(): void
    {
        $reservation = $this->makeReservation($this->makeUser('customer'), $this->makeProperty(), '2030-01-01', '2030-01-02');

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'data' => ['status' => 'success', 'reference' => $reservation->booking_ref, 'amount' => 100, 'currency' => 'NGN'],
            ]),
        ]);

        $this->get('/booking/callback?reference='.$reservation->booking_ref)->assertRedirect(route('home'));

        $this->assertSame('pending', $reservation->refresh()->payment_status);
    }

    public function test_confirmation_page_is_private(): void
    {
        $owner = $this->makeUser('customer');
        $reservation = $this->makeReservation($owner, $this->makeProperty(), '2030-01-01', '2030-01-02');
        $url = route('booking.confirmation', $reservation->booking_ref);

        $this->get($url)->assertForbidden();
        $this->actingAs($this->makeUser('customer'))->get($url)->assertForbidden();
        $this->actingAs($owner)->get($url)->assertOk();
    }

    public function test_webhook_requires_valid_signature_and_confirms(): void
    {
        $reservation = $this->makeReservation($this->makeUser('customer'), $this->makeProperty(), '2030-01-01', '2030-01-02');

        $body = json_encode([
            'event' => 'charge.success',
            'data' => [
                'status' => 'success',
                'reference' => $reservation->booking_ref,
                'amount' => 1075000,
                'currency' => 'NGN',
                'channel' => 'bank',
            ],
        ]);

        $this->call('POST', '/booking/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => 'bad',
        ], $body)->assertStatus(401);

        $this->assertSame('pending', $reservation->refresh()->payment_status);

        $eventKey = hash('sha256', 'paystack:'.hash('sha256', $body));
        PaymentEvent::create([
            'provider' => 'paystack',
            'event_key' => $eventKey,
            'event_type' => 'charge.success',
            'payload' => json_decode($body, true),
            'processed_at' => null,
        ]);

        $this->call('POST', '/booking/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $body, 'sk_test_secret'),
        ], $body)->assertOk();

        $this->assertSame('paid', $reservation->refresh()->payment_status);
        $this->assertDatabaseHas('payments', ['paystack_reference' => $reservation->booking_ref, 'paystack_status' => 'success']);
        $this->assertNotNull(PaymentEvent::where('event_key', $eventKey)->value('processed_at'));

        $this->call('POST', '/booking/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $body, 'sk_test_secret'),
        ], $body)->assertOk()->assertJson(['duplicate' => true]);

        $this->assertSame(1, Payment::where('reservation_id', $reservation->id)->count());
        $this->assertDatabaseCount('payment_events', 1);

        $refundBody = json_encode([
            'event' => 'refund.processed',
            'data' => [
                'id' => 98765,
                'amount' => 1075000,
                'currency' => 'NGN',
                'transaction_reference' => $reservation->booking_ref,
            ],
        ]);

        $this->call('POST', '/booking/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $refundBody, 'sk_test_secret'),
        ], $refundBody)->assertOk();

        $this->assertDatabaseHas('payments', [
            'paystack_reference' => $reservation->booking_ref,
            'type' => 'payment',
            'paystack_status' => 'success',
        ]);
        $this->assertDatabaseHas('payments', [
            'reservation_id' => $reservation->id,
            'type' => 'refund',
            'amount' => 10750,
            'paystack_status' => 'refunded',
        ]);
        $this->assertSame('refunded', $reservation->refresh()->payment_status);
    }

    public function test_availability_overlap_rules(): void
    {
        $property = $this->makeProperty();
        $this->makeReservation($this->makeUser('customer'), $property, '2030-03-10', '2030-03-15', ['booking_status' => 'confirmed']);
        $pricing = app(PricingService::class);

        // Back-to-back stays share a changeover day.
        $this->assertTrue($pricing->checkAvailability($property, '2030-03-15', '2030-03-17'));
        $this->assertTrue($pricing->checkAvailability($property, '2030-03-08', '2030-03-10'));

        foreach ([['2030-03-09', '2030-03-11'], ['2030-03-14', '2030-03-16'], ['2030-03-11', '2030-03-12'], ['2030-03-01', '2030-03-30']] as [$in, $out]) {
            try {
                $pricing->checkAvailability($property, $in, $out);
                $this->fail("Expected overlap for {$in} to {$out}");
            } catch (\Exception $e) {
                $this->assertSame('Selected dates are already booked', $e->getMessage());
            }
        }
    }
}
