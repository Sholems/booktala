<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * A first BookTala blog post explaining how booking works. Add more from Admin > Blog.
 */
class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::role('super_admin')->first();
        $brand = config('brand.name');

        Blog::updateOrCreate(
            ['slug' => 'how-to-book-your-stay-with-booktala'],
            [
                'title' => "How to Book Your Stay with {$brand}",
                'excerpt' => "Find an apartment, pay securely and check in online: here's how a {$brand} stay works from start to finish.",
                'content' => <<<HTML
<p>{$brand} is built to make short-let stays simple. Here's how it works, from finding an apartment to checking out.</p>
<h3>1. Choose your apartment</h3>
<p>Browse our apartments, compare photos, rooms and nightly rates, and filter by price, guests or bedrooms.</p>
<h3>2. Book and pay online</h3>
<p>Pick your dates and see the full price, including VAT, before you pay. Payment is processed securely by Paystack by card, bank transfer or USSD, and your confirmation arrives by email.</p>
<h3>3. Check in online</h3>
<p>Before you arrive, send your ID and arrival details from your account. At the front desk we simply confirm your ID and hand over the keys.</p>
<h3>4. Enjoy your stay</h3>
<p>Need towels, toiletries or laundry? Request it from your account and follow it until it's delivered. You can message our team at any time.</p>
<h3>5. Check out and share your experience</h3>
<p>After your stay, leave a review to help other guests and help us keep improving.</p>
HTML,
                'category' => 'Guides',
                'is_published' => true,
                'published_at' => now(),
                'meta_title' => "How to Book Your Stay with {$brand}",
                'author_id' => $admin?->id ?? 1,
            ]
        );
    }
}
