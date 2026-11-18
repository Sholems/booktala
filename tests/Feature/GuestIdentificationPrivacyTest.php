<?php

namespace Tests\Feature;

use App\Models\GuestCheckin;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GuestIdentificationPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_identification_values_are_encrypted_at_rest(): void
    {
        $user = User::factory()->create();
        $property = Property::create([
            'title' => 'Private Identity '.uniqid(), 'description' => 'Test apartment', 'address' => '1 Test Street',
            'city' => 'Lagos', 'state' => 'Lagos', 'price_per_night' => 10000, 'max_guests' => 2,
            'bedrooms' => 1, 'bathrooms' => 1, 'toilets' => 1, 'status' => 'active',
        ]);
        $reservation = Reservation::create([
            'booking_ref' => Reservation::generateBookingRef(), 'user_id' => $user->id, 'property_id' => $property->id,
            'check_in' => '2030-01-01', 'check_out' => '2030-01-03', 'nights' => 2, 'guests' => 1,
            'subtotal' => 20000, 'total_amount' => 20000, 'payment_status' => 'paid', 'booking_status' => 'confirmed',
        ]);
        $checkin = GuestCheckin::create([
            'reservation_id' => $reservation->id, 'check_in_date' => '2030-01-01', 'guest_first_name' => 'Ada',
            'guest_last_name' => 'Okafor', 'guest_email' => $user->email, 'guest_phone' => '08031234567',
            'id_type' => 'national_id', 'id_number' => 'NIN-12345678901', 'num_adults' => 1,
            'emergency_contact_name' => 'Chidi', 'emergency_contact_phone' => '08039887766',
        ]);

        $this->assertNotSame('NIN-12345678901', DB::table('guest_checkins')->where('id', $checkin->id)->value('id_number'));
        $this->assertSame('NIN-12345678901', $checkin->fresh()->id_number);
    }
}
