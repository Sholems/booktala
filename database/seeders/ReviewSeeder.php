<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Review;
use App\Models\Property;
use App\Models\User;
use Spatie\Permission\Models\Role;

class ReviewSeeder extends Seeder
{
    public function run()
    {
        $properties = Property::take(3)->get();
        
        $reviews = [
            [
                'overall_rating' => 5,
                'cleanliness_rating' => 5,
                'location_rating' => 5,
                'value_rating' => 5,
                'communication_rating' => 5,
                'comment' => 'Absolutely amazing stay! The apartment was spotless, well-furnished, and in a great location. The staff was very helpful and responsive. Will definitely book again!',
            ],
            [
                'overall_rating' => 5,
                'cleanliness_rating' => 5,
                'location_rating' => 5,
                'value_rating' => 4,
                'communication_rating' => 5,
                'comment' => 'Perfect for business travel. Fast WiFi, comfortable workspace, and quiet environment. The check-in process was smooth and the host was very professional.',
            ],
            [
                'overall_rating' => 4,
                'cleanliness_rating' => 4,
                'location_rating' => 5,
                'value_rating' => 4,
                'communication_rating' => 4,
                'comment' => 'Great location and clean apartment. The amenities were as described. Minor issue with parking but overall a wonderful experience.',
            ],
            [
                'overall_rating' => 5,
                'cleanliness_rating' => 5,
                'location_rating' => 5,
                'value_rating' => 5,
                'communication_rating' => 5,
                'comment' => 'Exceeded my expectations! The place was beautiful, modern, and had everything I needed. Highly recommend for anyone visiting Lagos.',
            ],
            [
                'overall_rating' => 5,
                'cleanliness_rating' => 5,
                'location_rating' => 5,
                'value_rating' => 5,
                'communication_rating' => 5,
                'comment' => 'Second time staying here and it was just as great as the first. The apartment feels like home and the neighborhood is safe and convenient.',
            ],
        ];

        $user = User::first() ?? User::create([
            'name' => 'Demo User',
            'email' => 'demo@booktala.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        if (Role::where('name', 'customer')->exists() && !$user->hasRole('customer')) {
            $user->assignRole('customer');
        }

        foreach ($properties as $index => $property) {
            if (isset($reviews[$index])) {
                Review::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'property_id' => $property->id,
                    ],
                    [
                        'overall_rating' => $reviews[$index]['overall_rating'],
                        'cleanliness_rating' => $reviews[$index]['cleanliness_rating'],
                        'location_rating' => $reviews[$index]['location_rating'],
                        'value_rating' => $reviews[$index]['value_rating'],
                        'communication_rating' => $reviews[$index]['communication_rating'],
                        'comment' => $reviews[$index]['comment'],
                        'is_published' => true,
                    ]
                );
            }
        }
    }
}
