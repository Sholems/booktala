<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $properties = Property::active()->get();
        if ($properties->isEmpty()) {
            return;
        }

        // Find or create test guest users for authentic reviews
        $guests = [
            [
                'name' => 'Dr. Babatunde Adeleke',
                'email' => 'babatunde.adeleke@example.com',
                'phone' => '+2348031234001',
                'property_index' => 0,
                'rating' => 5,
                'comment' => 'The 24/7 uninterrupted power was a lifesaver during my 10-day business trip to Lagos. The fiber internet was rock solid for Zoom meetings, and the security at the estate was exceptionally polite and professional. Will definitely stay here again.',
                'cleanliness' => 5,
                'location' => 5,
                'value' => 5,
                'communication' => 5,
            ],
            [
                'name' => 'Chioma Okonkwo',
                'email' => 'chioma.okonkwo@example.com',
                'phone' => '+2348031234002',
                'property_index' => 1,
                'rating' => 5,
                'comment' => 'Booked this apartment for a family holiday. The kitchen was fully equipped, the beds were hotel-grade comfortable, and the self check-in was seamless. My caution deposit was returned in under 24 hours without even asking.',
                'cleanliness' => 5,
                'location' => 5,
                'value' => 5,
                'communication' => 5,
            ],
            [
                'name' => 'Femi Rowland',
                'email' => 'femi.rowland@example.com',
                'phone' => '+2348031234003',
                'property_index' => 2,
                'rating' => 5,
                'comment' => 'Super clean and serene environment in Lekki Phase 1. The air conditioning was freezing cold, hot water was consistent, and the concierge was always a WhatsApp message away. Top notch hospitality standard.',
                'cleanliness' => 5,
                'location' => 5,
                'value' => 5,
                'communication' => 5,
            ],
            [
                'name' => 'Sarah Jenkins',
                'email' => 'sarah.jenkins@example.com',
                'phone' => '+2348031234004',
                'property_index' => 3,
                'rating' => 5,
                'comment' => 'As an expatriate visiting Lagos for consulting, BookTala gave me peace of mind. Gated estate security, modern interior aesthetics, and zero power cuts. Better and more private than most 5-star hotels in the area.',
                'cleanliness' => 5,
                'location' => 5,
                'value' => 5,
                'communication' => 5,
            ],
        ];

        foreach ($guests as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'password' => bcrypt('password123'),
                ]
            );

            $property = $properties[$data['property_index'] % $properties->count()];

            Review::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'property_id' => $property->id,
                ],
                [
                    'cleanliness_rating' => $data['cleanliness'],
                    'location_rating' => $data['location'],
                    'value_rating' => $data['value'],
                    'communication_rating' => $data['communication'],
                    'overall_rating' => $data['rating'],
                    'comment' => $data['comment'],
                    'is_published' => true,
                    'created_at' => now()->subDays(rand(2, 28)),
                ]
            );
        }
    }
}
