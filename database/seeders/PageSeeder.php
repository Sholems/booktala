<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Standard BookTala pages. The policies are a starting template: have them
 * reviewed for G-Briel Property Ltd, then edit them in Admin > Pages.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $brand = config('brand.name');
        $owner = config('brand.owner');
        $contact = 'the contact details on our <a href="/contact">contact page</a>';

        $pages = [
            [
                'title' => 'About Us',
                'slug' => 'about-us',
                'content' => "<h2>{$brand}: Stays, Travels, Better Living</h2>
<p>".e(config('brand.description'))."</p>
<p>{$brand} is a brand of {$owner}.</p>",
                'meta_title' => "About Us - {$brand}",
                'meta_description' => "{$brand} makes short-let stays simple to find, book and enjoy.",
            ],
            [
                'title' => 'Contact Us',
                'slug' => 'contact-us',
                'content' => "<h2>Get in touch with {$brand}</h2>
<p>Questions about a booking, an apartment or a special request? Reach us using {$contact}.</p>",
                'meta_title' => "Contact Us - {$brand}",
                'meta_description' => "Contact {$brand} about bookings, apartments and special requests.",
            ],
            [
                'title' => 'Terms and Conditions',
                'slug' => 'terms-and-conditions',
                'content' => "<h2>Terms and Conditions</h2>
<p>These terms apply to every booking made through {$brand}, a brand of {$owner} (\"we\", \"us\"). By booking or staying with us you agree to them.</p>
<h3>1. Bookings and payment</h3>
<ul>
<li>A booking is confirmed when payment is received and a confirmation is sent to you by email.</li>
<li>Prices are per night and shown with VAT before you pay. Payments are processed securely by Paystack.</li>
<li>Guests must be at least 18 years old to book. Minors must be accompanied by an adult.</li>
</ul>
<h3>2. Check-in and check-out</h3>
<ul>
<li>Check-in is from 2:00 PM and check-out is by 12:00 PM, unless your apartment states otherwise.</li>
<li>A valid government-issued ID or passport matching your booking is required at check-in.</li>
<li>Early check-in and late check-out are subject to availability and may carry a fee.</li>
</ul>
<h3>3. During your stay</h3>
<ul>
<li>Only the number of guests on the booking may stay overnight.</li>
<li>Smoking is not allowed inside the apartments. Pets need prior written approval.</li>
<li>Parties and events need prior approval.</li>
<li>Please respect neighbours and keep noise to a minimum.</li>
</ul>
<h3>4. Damage and caution fee</h3>
<p>Guests are responsible for damage caused during their stay. Where an apartment requires a refundable caution fee, it is shown before you pay and returned after check-out, less the cost of any damage.</p>
<h3>5. Cancellations</h3>
<p>Cancellations and refunds follow our <a href=\"/cancellation-policy\">cancellation policy</a> and the policy shown on each apartment.</p>
<h3>6. Liability</h3>
<p>We are not responsible for loss of or damage to guests' personal belongings, except where caused by our negligence.</p>
<h3>7. Contact</h3>
<p>For questions about these terms, use {$contact}.</p>",
                'meta_title' => "Terms and Conditions - {$brand}",
                'meta_description' => "Terms and conditions for bookings and stays with {$brand}.",
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => "<h2>Privacy Policy</h2>
<p>{$owner} operates {$brand} and is responsible for the personal data you share with us. This policy explains what we collect and why.</p>
<h3>What we collect</h3>
<ul>
<li>Your name, email address and phone number</li>
<li>Booking details, such as dates, guests and special requests</li>
<li>Identification details you provide for check-in</li>
<li>Payment confirmations from Paystack (we never store your card details)</li>
</ul>
<h3>How we use it</h3>
<ul>
<li>To manage your booking, check-in and stay</li>
<li>To send booking confirmations, receipts and reminders</li>
<li>To keep our guests and apartments safe and meet legal obligations</li>
</ul>
<h3>Sharing</h3>
<p>We do not sell your data. We share it only with service providers who help us run bookings and payments, or where the law requires it.</p>
<h3>Your rights</h3>
<p>You can ask to see, correct or delete your personal data. To do so, use {$contact}.</p>",
                'meta_title' => "Privacy Policy - {$brand}",
                'meta_description' => "How {$brand} collects, uses and protects your personal data.",
            ],
            [
                'title' => 'Cancellation Policy',
                'slug' => 'cancellation-policy',
                'content' => "<h2>Cancellation Policy</h2>
<p>You can cancel a booking online from your account before your check-in date. Unless an apartment states a different policy, refunds work as follows:</p>
<ul>
<li><strong>More than 72 hours before check-in:</strong> full refund</li>
<li><strong>Between 72 and 24 hours before check-in:</strong> 50% refund</li>
<li><strong>Less than 24 hours before check-in, or no-show:</strong> no refund</li>
</ul>
<p>Approved refunds are returned to the original payment method. Bank processing times vary.</p>
<p>To change your dates instead of cancelling, use {$contact}.</p>",
                'meta_title' => "Cancellation Policy - {$brand}",
                'meta_description' => "How cancellations and refunds work at {$brand}.",
            ],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(['slug' => $page['slug']], $page + ['is_published' => true]);
        }
    }
}
