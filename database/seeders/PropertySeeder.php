<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Sample BookTala listings to start from. Names, prices, room counts and
 * photos are placeholders: replace them with G-Briel Property Ltd's real
 * apartments from Admin > Properties.
 */
class PropertySeeder extends Seeder
{
    private const HOUSE_RULES = "Valid government-issued ID is required at check-in.\nGuests must be at least 18 years old to book; minors must be accompanied by an adult.\nSmoking is not allowed inside the apartment.\nNo pets without prior written approval.\nParties and events require prior approval.\nPlease keep noise to a minimum out of respect for neighbours.";

    private const CANCELLATION_POLICY = 'Free cancellation up to 72 hours before check-in. Cancellations made between 72 and 24 hours before check-in receive a 50% refund. No refund for cancellations within 24 hours of check-in or for no-shows.';

    private const COMMON_AMENITIES = [
        'Air Conditioning', 'Smart TV', 'WiFi', 'Kitchen', 'Parking Space',
        '24/7 Power Supply', '24/7 Security',
    ];

    public function run(): void
    {
        $admin = User::role('super_admin')->first();

        $properties = [
            [
                'title' => 'Executive 3-Bedroom Apartment',
                'slug' => 'executive-3-bedroom-apartment',
                'short_description' => 'Spacious three-bedroom home for families and groups, with a large lounge and fully equipped kitchen.',
                'description' => "A spacious three-bedroom apartment with an open-plan lounge, dining area and fully equipped kitchen. Each bedroom has its own en-suite bathroom, making it ideal for families, groups and business teams.\n\nEnjoy fast WiFi, air-conditioning throughout, constant power and secure parking, with our team a message away for anything you need.",
                'price_per_night' => 150000,
                'bedrooms' => 3, 'bathrooms' => 3, 'max_guests' => 6,
                'amenities' => ['Washing Machine'],
            ],
            [
                'title' => 'Classic 2-Bedroom Apartment',
                'slug' => 'classic-2-bedroom-apartment',
                'short_description' => 'A bright two-bedroom apartment with room to relax, cook and work.',
                'description' => "A bright, comfortable two-bedroom apartment with a cosy living room, a kitchen ready for home cooking and a quiet workspace.\n\nPerfect for small families, friends travelling together or longer business stays.",
                'price_per_night' => 95000,
                'bedrooms' => 2, 'bathrooms' => 2, 'max_guests' => 4,
                'amenities' => [],
            ],
            [
                'title' => 'Deluxe 1-Bedroom Apartment',
                'slug' => 'deluxe-1-bedroom-apartment',
                'short_description' => 'A stylish one-bedroom apartment for couples and solo travellers.',
                'description' => "A stylish one-bedroom apartment with a separate living area, a compact kitchen and a calm, well-lit bedroom.\n\nIdeal for couples, solo travellers and business guests who want more space than a hotel room.",
                'price_per_night' => 65000,
                'bedrooms' => 1, 'bathrooms' => 1, 'max_guests' => 2,
                'amenities' => ['Coffee Maker', 'Hairdryer'],
            ],
            [
                'title' => 'Smart Studio Apartment',
                'slug' => 'smart-studio-apartment',
                'short_description' => 'A smart, affordable studio with everything you need for a short stay.',
                'description' => "A smart open-plan studio with a comfortable bed, a kitchenette and a work desk.\n\nGreat value for short stays, solo trips and quick business visits.",
                'price_per_night' => 45000,
                'bedrooms' => 1, 'bathrooms' => 1, 'max_guests' => 2,
                'amenities' => [],
            ],
        ];

        foreach ($properties as $data) {
            $unit = Unit::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'created_by' => $admin?->id,
                    'title' => $data['title'],
                    'short_description' => $data['short_description'],
                    'description' => $data['description'],
                    'address' => config('brand.address'),
                    'city' => 'Lagos',
                    'state' => 'Lagos',
                    'country' => 'Nigeria',
                    'price_per_night' => $data['price_per_night'],
                    'bedrooms' => $data['bedrooms'],
                    'bathrooms' => $data['bathrooms'],
                    'toilets' => $data['bathrooms'],
                    'max_guests' => $data['max_guests'],
                    'status' => 'active',
                    'is_featured' => true,
                    'check_in_time' => '14:00:00',
                    'check_out_time' => '12:00:00',
                    'house_rules' => self::HOUSE_RULES,
                    'cancellation_policy' => self::CANCELLATION_POLICY,
                    'meta_title' => $data['title'].' | '.config('brand.name'),
                    'meta_description' => $data['short_description'],
                ]
            );

            $unit->amenities()->sync(
                Amenity::whereIn('name', array_merge(self::COMMON_AMENITIES, $data['amenities']))->pluck('id')
            );
        }
    }
}
