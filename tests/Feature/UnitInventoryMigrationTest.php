<?php

namespace Tests\Feature;

use App\Models\Owner;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Site;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UnitInventoryMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_property_identity_and_public_url_are_preserved_through_unit_model(): void
    {
        $property = $this->makeProperty('Preserved Apartment');
        $guest = User::factory()->create();
        $reservation = $this->makeReservation($property, $guest);

        $unit = Unit::findOrFail($property->id);

        $this->assertSame($property->id, $unit->id);
        $this->assertSame($property->slug, $unit->slug);
        $this->assertSame($property->owner_id, $unit->owner_id);
        $this->assertSame($property->site_id, $unit->site_id);
        $this->assertSame($reservation->id, $unit->reservations()->firstOrFail()->id);
        $this->assertSame('/apartments/'.$property->slug, parse_url(route('properties.show', $property->slug), PHP_URL_PATH));
    }

    public function test_database_rejects_a_unit_without_owner_and_site(): void
    {
        $this->expectException(QueryException::class);

        DB::table('properties')->insert([
            'title' => 'Unowned Unit',
            'slug' => 'unowned-unit',
            'description' => 'Invalid active unit',
            'address' => '1 Test Street',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'price_per_night' => 10000,
            'max_guests' => 2,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'toilets' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_audit_automatically_maps_a_room_used_by_one_unit(): void
    {
        $property = $this->makeProperty('Mapped Apartment');
        $room = $this->makeRoom('101');
        $this->makeReservation($property, User::factory()->create(), $room);

        $this->artisan('units:audit-migration', ['--apply' => true])
            ->expectsOutputToContain('automatic: 1')
            ->expectsOutputToContain('blocking: 0')
            ->assertSuccessful();

        $room->refresh();
        $this->assertSame($property->id, $room->unit_id);
        $this->assertSame('automatic', $room->unit_mapping_status);
    }

    public function test_audit_blocks_rooms_with_conflicting_or_missing_relationships(): void
    {
        $first = $this->makeProperty('First Apartment');
        $second = $this->makeProperty('Second Apartment');
        $conflicting = $this->makeRoom('201');
        $unrelated = $this->makeRoom('202');
        $guest = User::factory()->create();

        $this->makeReservation($first, $guest, $conflicting);
        $this->makeReservation($second, $guest, $conflicting);

        $this->artisan('units:audit-migration', ['--apply' => true])
            ->expectsOutputToContain('blocking: 2')
            ->assertFailed();

        $this->assertSame('blocking', $conflicting->fresh()->unit_mapping_status);
        $this->assertSame('blocking', $unrelated->fresh()->unit_mapping_status);
        $this->assertNull($conflicting->fresh()->unit_id);
        $this->assertNull($unrelated->fresh()->unit_id);
    }

    public function test_owner_and_site_with_historical_units_cannot_be_deleted(): void
    {
        $property = $this->makeProperty('Historical Apartment');

        try {
            $property->owner->delete();
            $this->fail('Owner deletion should have been restricted.');
        } catch (QueryException) {
            $this->assertDatabaseHas('owners', ['id' => $property->owner_id]);
        }

        try {
            $property->site->delete();
            $this->fail('Site deletion should have been restricted.');
        } catch (QueryException) {
            $this->assertDatabaseHas('sites', ['id' => $property->site_id]);
        }
    }

    private function makeProperty(string $title): Property
    {
        $owner = Owner::create([
            'name' => $title.' Owner',
            'type' => 'individual',
            'is_active' => true,
        ]);
        $site = Site::create([
            'name' => $title.' Site',
            'address' => '1 Test Street',
            'city' => 'Lagos',
            'state' => 'Lagos',
        ]);

        return Property::create([
            'title' => $title,
            'description' => 'A test unit',
            'address' => '1 Test Street',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'price_per_night' => 10000,
            'max_guests' => 2,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'toilets' => 1,
            'status' => 'active',
            'owner_id' => $owner->id,
            'site_id' => $site->id,
        ]);
    }

    private function makeRoom(string $number): Room
    {
        $roomType = RoomType::create([
            'name' => 'Legacy '.$number,
            'slug' => 'legacy-'.$number,
            'base_price' => 10000,
            'total_rooms' => 1,
        ]);

        return Room::create([
            'room_type_id' => $roomType->id,
            'room_number' => $number,
            'status' => 'available',
        ]);
    }

    private function makeReservation(Property $property, User $guest, ?Room $room = null): Reservation
    {
        return Reservation::create([
            'booking_ref' => Reservation::generateBookingRef(),
            'user_id' => $guest->id,
            'property_id' => $property->id,
            'room_id' => $room?->id,
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
