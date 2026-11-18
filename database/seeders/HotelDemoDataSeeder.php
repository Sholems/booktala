<?php

namespace Database\Seeders;

use App\Models\HotelAmenity;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HotelDemoDataSeeder extends Seeder
{
    public function run()
    {
        $roomTypes = [
            ['name' => 'Standard Room', 'base_price' => 25000, 'total_rooms' => 10, 'max_adults' => 2, 'short_description' => 'Comfortable standard room with essential amenities'],
            ['name' => 'Deluxe Room', 'base_price' => 35000, 'total_rooms' => 8, 'max_adults' => 2, 'short_description' => 'Spacious deluxe room with modern decor'],
            ['name' => 'Executive Suite', 'base_price' => 55000, 'total_rooms' => 5, 'max_adults' => 3, 'short_description' => 'Luxurious suite with living area and premium amenities'],
            ['name' => 'Presidential Suite', 'base_price' => 120000, 'total_rooms' => 2, 'max_adults' => 4, 'short_description' => 'Ultimate luxury suite with panoramic views'],
        ];

        foreach ($roomTypes as $type) {
            $roomType = RoomType::create([
                'name' => $type['name'],
                'slug' => Str::slug($type['name']),
                'base_price' => $type['base_price'],
                'total_rooms' => $type['total_rooms'],
                'max_adults' => $type['max_adults'],
                'max_children' => 1,
                'short_description' => $type['short_description'],
                'bed_type' => 'double',
                'is_active' => true,
            ]);

            for ($i = 1; $i <= $type['total_rooms']; $i++) {
                Room::create([
                    'room_type_id' => $roomType->id,
                    'room_number' => (string) $i,
                    'floor' => rand(1, 5),
                    'status' => 'available',
                ]);
            }
        }

        $amenities = [
            ['name' => 'Swimming Pool', 'icon' => 'fa-swimming-pool', 'category' => 'recreation', 'opening_time' => '06:00', 'closing_time' => '22:00'],
            ['name' => 'Fitness Center', 'icon' => 'fa-dumbbell', 'category' => 'fitness', 'opening_time' => '00:00', 'closing_time' => '23:59'],
            ['name' => 'Spa & Wellness', 'icon' => 'fa-spa', 'category' => 'wellness', 'opening_time' => '09:00', 'closing_time' => '21:00'],
            ['name' => 'Restaurant', 'icon' => 'fa-utensils', 'category' => 'dining', 'opening_time' => '06:00', 'closing_time' => '23:00'],
            ['name' => 'Bar & Lounge', 'icon' => 'fa-cocktail', 'category' => 'dining', 'opening_time' => '12:00', 'closing_time' => '00:00'],
            ['name' => 'Conference Room', 'icon' => 'fa-chalkboard-teacher', 'category' => 'business', 'opening_time' => '08:00', 'closing_time' => '20:00'],
        ];

        foreach ($amenities as $amenity) {
            HotelAmenity::create([
                'name' => $amenity['name'],
                'slug' => Str::slug($amenity['name']),
                'icon' => $amenity['icon'],
                'category' => $amenity['category'],
                'opening_time' => $amenity['opening_time'],
                'closing_time' => $amenity['closing_time'],
                'is_active' => true,
            ]);
        }
    }
}
