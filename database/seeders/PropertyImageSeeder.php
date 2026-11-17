<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Database\Seeder;

/**
 * Stock interior photos (Unsplash) for the sample listings, primary image first.
 * Replace them with photos of the real apartments from Admin > Properties.
 */
class PropertyImageSeeder extends Seeder
{
    private const PHOTO = 'https://images.unsplash.com/photo-%s?auto=format&fit=crop&w=1200&q=80';

    public function run(): void
    {
        $images = [
            'executive-3-bedroom-apartment' => ['1600585154340-be6161a56a0c', '1586023492125-27b2c045efd7', '1484154218962-a197022b5858'],
            'classic-2-bedroom-apartment' => ['1522708323590-d24dbb6b0267', '1540518614846-7eded433c457', '1556911220-bff31c812dba'],
            'deluxe-1-bedroom-apartment' => ['1502672260266-1c1ef2d93688', '1505693416388-ac5ce068fe85', '1554995207-c18c203602cb'],
            'smart-studio-apartment' => ['1493809842364-78817add7ffb', '1631049307264-da0ec9d70304', '1512918728675-ed5a9ecdebfd'],
        ];

        foreach (Property::whereIn('slug', array_keys($images))->get() as $property) {
            foreach ($images[$property->slug] as $index => $id) {
                PropertyImage::updateOrCreate(
                    ['property_id' => $property->id, 'image_path' => sprintf(self::PHOTO, $id)],
                    ['is_primary' => $index === 0, 'sort_order' => $index + 1]
                );
            }
        }
    }
}
