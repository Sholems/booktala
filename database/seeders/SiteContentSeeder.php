<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * BookTala website content: settings,
 * apartments, photos, homepage sections, policy pages and blog posts.
 *
 * Safe to re-run on an existing install:
 *   php artisan db:seed --class=SiteContentSeeder
 */
class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AmenitySeeder::class,
            SettingsSeeder::class,
            PropertySeeder::class,
            PropertyImageSeeder::class,
            HomepageSectionSeeder::class,
            PageSeeder::class,
            BlogSeeder::class,
        ]);
    }
}
