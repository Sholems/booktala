<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\User;
use App\Services\GuestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestIdentityMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_customer_history_is_backfilled_and_survives_user_deletion(): void
    {
        $user = User::factory()->create(['phone' => '0803 123 4567']);
        $reservation = $this->makeReservation($user);
        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'user_id' => $user->id,
            'amount' => 20000,
            'paystack_reference' => 'PAY-'.uniqid(),
            'type' => 'payment',
        ]);
        $invoice = Invoice::create([
            'reservation_id' => $reservation->id,
            'user_id' => $user->id,
            'invoice_number' => 'INV-'.uniqid(),
            'subtotal' => 20000,
            'total_amount' => 20000,
            'balance' => 20000,
        ]);

        $guest = Guest::where('user_id', $user->id)->firstOrFail();

        $this->assertSame($guest->id, $reservation->fresh()->guest_id);
        $this->assertSame($guest->id, $payment->fresh()->guest_id);
        $this->assertSame($guest->id, $invoice->fresh()->guest_id);

        User::withoutEvents(fn () => $user->delete());

        $this->assertDatabaseHas('guests', ['id' => $guest->id, 'user_id' => null]);
        $this->assertDatabaseHas('reservations', ['id' => $reservation->id, 'guest_id' => $guest->id, 'user_id' => null]);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'guest_id' => $guest->id, 'user_id' => null]);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'guest_id' => $guest->id, 'user_id' => null]);
    }

    public function test_phone_only_guest_can_later_link_to_a_portal_user(): void
    {
        $guest = Guest::create(['name' => 'Walk In Guest', 'phone' => '08031234567']);
        $user = User::factory()->create(['email' => 'walkin@example.com', 'phone' => '08031234567']);

        app(GuestService::class)->linkUser($guest, $user);

        $this->assertSame($user->id, $guest->fresh()->user_id);
    }

    public function test_guest_service_requires_explicit_choice_for_conflicting_contact_matches(): void
    {
        $first = Guest::create(['name' => 'Phone Match', 'phone' => '08031234567']);
        $second = Guest::create(['name' => 'Email Match', 'email' => 'match@example.com']);

        $match = app(GuestService::class)->match(['phone' => '0803-123-4567', 'email' => 'MATCH@example.com']);

        $this->assertTrue($match->requiresSelection());
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $match->guests->pluck('id')->all());
    }

    private function makeReservation(User $user): Reservation
    {
        $property = Property::create([
            'title' => 'Guest History '.uniqid(),
            'description' => 'Test apartment',
            'address' => '1 Test Street',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'price_per_night' => 10000,
            'max_guests' => 2,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'toilets' => 1,
            'status' => 'active',
        ]);

        return Reservation::create([
            'booking_ref' => Reservation::generateBookingRef(),
            'user_id' => $user->id,
            'property_id' => $property->id,
            'check_in' => '2030-01-01',
            'check_out' => '2030-01-03',
            'nights' => 2,
            'guests' => 1,
            'subtotal' => 20000,
            'total_amount' => 20000,
            'payment_status' => 'paid',
            'booking_status' => 'confirmed',
        ]);
    }
}
