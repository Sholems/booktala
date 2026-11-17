<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Site settings, taken from config/brand.php. Existing values are never
 * overwritten, so anything changed in Admin > Settings is kept on re-seed.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'site_name', 'value' => config('brand.name'), 'group' => 'general'],
            ['key' => 'tagline', 'value' => config('brand.tagline'), 'group' => 'general'],
            ['key' => 'site_description', 'value' => config('brand.description'), 'group' => 'general'],
            ['key' => 'company_name', 'value' => config('brand.owner'), 'group' => 'general'],
            ['key' => 'email', 'value' => config('brand.email'), 'group' => 'contact'],
            ['key' => 'phone', 'value' => config('brand.phone'), 'group' => 'contact'],
            ['key' => 'address', 'value' => config('brand.address'), 'group' => 'contact'],
            ['key' => 'social_facebook', 'value' => config('brand.social.facebook'), 'group' => 'social'],
            ['key' => 'social_instagram', 'value' => config('brand.social.instagram'), 'group' => 'social'],
            ['key' => 'social_twitter', 'value' => config('brand.social.twitter'), 'group' => 'social'],
            ['key' => 'google_profile', 'value' => config('brand.google_profile'), 'group' => 'social'],
            ['key' => 'mail_from_name', 'value' => config('brand.name'), 'group' => 'mail'],
            ['key' => 'check_in_time', 'value' => '14:00', 'group' => 'booking'],
            ['key' => 'check_out_time', 'value' => '12:00', 'group' => 'booking'],
            ['key' => 'caution_fee', 'value' => '0', 'group' => 'booking'],
            ['key' => 'vat_rate', 'value' => '7.5', 'group' => 'payment'],
            ['key' => 'min_advance_booking_hours', 'value' => '2', 'group' => 'booking'],
            ['key' => 'max_booking_window_days', 'value' => '365', 'group' => 'booking'],
            ['key' => 'currency', 'value' => 'NGN', 'group' => 'payment'],
            ['key' => 'currency_symbol', 'value' => '₦', 'group' => 'payment'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
