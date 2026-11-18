<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;

class AmenitySeeder extends Seeder
{
    public function run(): void
    {
        $amenities = [
            ['name' => 'Air Conditioning', 'icon' => 'fa-snowflake', 'category' => 'basic'],
            ['name' => 'Fan', 'icon' => 'fa-fan', 'category' => 'basic'],
            ['name' => 'Bedroom Essentials', 'icon' => 'fa-bed', 'category' => 'basic'],
            ['name' => 'Smart TV', 'icon' => 'fa-tv', 'category' => 'basic'],
            ['name' => 'WiFi', 'icon' => 'fa-wifi', 'category' => 'basic'],
            ['name' => 'Kitchen', 'icon' => 'fa-utensils', 'category' => 'basic'],
            ['name' => 'Refrigerator', 'icon' => 'fa-box', 'category' => 'basic'],
            ['name' => 'Microwave', 'icon' => 'fa-microwave', 'category' => 'basic'],
            ['name' => 'Washing Machine', 'icon' => 'fa-soap', 'category' => 'basic'],
            ['name' => 'Iron', 'icon' => 'fa-iron', 'category' => 'basic'],
            ['name' => 'Private Entrance', 'icon' => 'fa-door-open', 'category' => 'luxury'],
            ['name' => 'Jacuzzi', 'icon' => 'fa-bath', 'category' => 'luxury'],
            ['name' => 'Swimming Pool', 'icon' => 'fa-swimmer', 'category' => 'luxury'],
            ['name' => 'Gym', 'icon' => 'fa-dumbbell', 'category' => 'luxury'],
            ['name' => 'Cinema Room', 'icon' => 'fa-film', 'category' => 'luxury'],
            ['name' => 'Security System', 'icon' => 'fa-shield-alt', 'category' => 'safety'],
            ['name' => 'CCTV', 'icon' => 'fa-video', 'category' => 'safety'],
            ['name' => 'Fire Extinguisher', 'icon' => 'fa-fire-extinguisher', 'category' => 'safety'],
            ['name' => 'First Aid Kit', 'icon' => 'fa-medkit', 'category' => 'safety'],
            ['name' => '24/7 Security', 'icon' => 'fa-user-shield', 'category' => 'safety'],
            ['name' => 'Balcony', 'icon' => 'fa-archway', 'category' => 'outdoor'],
            ['name' => 'Garden', 'icon' => 'fa-leaf', 'category' => 'outdoor'],
            ['name' => 'Terrace', 'icon' => 'fa-sun', 'category' => 'outdoor'],
            ['name' => 'Parking Space', 'icon' => 'fa-parking', 'category' => 'outdoor'],
            ['name' => 'BBQ Area', 'icon' => 'fa-fire', 'category' => 'outdoor'],
            ['name' => '24/7 Power Supply', 'icon' => 'fa-bolt', 'category' => 'basic'],
            ['name' => 'Filtered Borehole Water', 'icon' => 'fa-tint', 'category' => 'basic'],
            ['name' => 'Coffee Maker', 'icon' => 'fa-mug-hot', 'category' => 'basic'],
            ['name' => 'Hairdryer', 'icon' => 'fa-wind', 'category' => 'basic'],
            ['name' => 'Games Area', 'icon' => 'fa-gamepad', 'category' => 'luxury'],
        ];

        foreach ($amenities as $amenity) {
            Amenity::firstOrCreate(['name' => $amenity['name']], $amenity);
        }
    }
}
