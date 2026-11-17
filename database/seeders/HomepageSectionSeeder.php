<?php

namespace Database\Seeders;

use App\Models\HomepageSection;
use Illuminate\Database\Seeder;

/**
 * BookTala homepage content. Every section is editable in Admin > Homepage.
 * Guest reviews and the blog stay hidden until there are real ones to show.
 */
class HomepageSectionSeeder extends Seeder
{
    public function run(): void
    {
        $brand = config('brand.name');

        $sections = [
            [
                'section_key' => 'hero',
                'title' => "Welcome to {$brand}",
                'subtitle' => config('brand.tagline'),
                'content' => config('brand.city'),
                'image' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1920&q=80',
                'button_text' => 'Book Your Stay',
                'button_link' => '/apartments',
                'order' => 0,
                'is_visible' => true,
            ],
            [
                'section_key' => 'stats',
                'title' => "Why book with {$brand}",
                'subtitle' => null,
                'content' => json_encode([
                    ['value' => '24/7', 'label' => 'Online booking'],
                    ['value' => '100%', 'label' => 'Secure payments'],
                    ['value' => '1 min', 'label' => 'Online check-in'],
                    ['value' => 'Live', 'label' => 'Request tracking'],
                ]),
                'order' => 1,
                'is_visible' => true,
            ],
            [
                'section_key' => 'welcome',
                'title' => "Welcome to {$brand}",
                'subtitle' => config('brand.tagline'),
                'content' => config('brand.description') . "\n\nBook online in minutes, check in before you arrive, and reach our team any time during your stay.",
                'image' => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=1200&q=80',
                'button_text' => 'About BookTala',
                'button_link' => '/about',
                'order' => 2,
                'is_visible' => true,
            ],
            [
                'section_key' => 'how_it_works',
                'title' => 'Book in three simple steps',
                'subtitle' => 'From browsing to checking in, everything happens online',
                'content' => json_encode([
                    ['icon' => 'fa-search', 'title' => 'Choose', 'description' => 'Browse our apartments and compare rooms, photos and prices'],
                    ['icon' => 'fa-credit-card', 'title' => 'Book and pay', 'description' => 'Pick your dates and pay securely online with Paystack'],
                    ['icon' => 'fa-key', 'title' => 'Check in and relax', 'description' => 'Check in online, then enjoy a home with hotel comforts'],
                ]),
                'order' => 3,
                'is_visible' => true,
            ],
            [
                'section_key' => 'featured_properties',
                'title' => 'Our Apartments',
                'subtitle' => 'Fully furnished homes for short and long stays',
                'content' => null,
                'order' => 4,
                'is_visible' => true,
            ],
            [
                'section_key' => 'amenities',
                'title' => 'Everything you need for a great stay',
                'subtitle' => 'Comfort and convenience in every apartment',
                'content' => json_encode([
                    ['icon' => 'fa-bolt', 'title' => 'Reliable power', 'description' => 'Backup power so your stay is never interrupted.'],
                    ['icon' => 'fa-wifi', 'title' => 'High-speed WiFi', 'description' => 'Fast internet for work, streaming and staying in touch.'],
                    ['icon' => 'fa-snowflake', 'title' => 'Air-conditioning', 'description' => 'Cool, comfortable rooms throughout.'],
                    ['icon' => 'fa-utensils', 'title' => 'Equipped kitchens', 'description' => 'Cook your own meals whenever you like.'],
                    ['icon' => 'fa-parking', 'title' => 'Secure parking', 'description' => 'Safe parking within a secured compound.'],
                    ['icon' => 'fa-user-shield', 'title' => '24/7 security', 'description' => 'Gated access and security for peace of mind.'],
                ]),
                'order' => 5,
                'is_visible' => true,
            ],
            [
                'section_key' => 'pricing',
                'title' => 'Our Rates',
                'subtitle' => 'Simple, transparent nightly rates. VAT is shown before you pay.',
                'content' => json_encode([
                    'regular' => [
                        ['type' => '3 Bedroom Apartment', 'price' => '₦150,000'],
                        ['type' => '2 Bedroom Apartment', 'price' => '₦95,000'],
                        ['type' => '1 Bedroom Apartment', 'price' => '₦65,000'],
                        ['type' => 'Studio Apartment', 'price' => '₦45,000'],
                    ],
                    'party' => [],
                ]),
                'order' => 6,
                'is_visible' => true,
            ],
            [
                'section_key' => 'reviews',
                'title' => 'Guest Feedback',
                'subtitle' => 'What our guests say about their stay',
                'content' => json_encode([]),
                'order' => 7,
                'is_visible' => false,
            ],
            [
                'section_key' => 'blog',
                'title' => 'Latest from Our Blog',
                'subtitle' => 'Travel tips and guides for your stay',
                'content' => null,
                'order' => 8,
                'is_visible' => false,
            ],
            [
                'section_key' => 'cta',
                'title' => 'Ready to book your stay?',
                'subtitle' => 'Book online in minutes. Business travellers, families and holidaymakers welcome.',
                'content' => null,
                'image' => null,
                'button_text' => 'Book Now',
                'button_link' => '/apartments',
                'order' => 9,
                'is_visible' => true,
            ],
        ];

        foreach ($sections as $section) {
            HomepageSection::updateOrCreate(['section_key' => $section['section_key']], $section);
        }
    }
}
