<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * The first super admin. Set ADMIN_EMAIL and ADMIN_PASSWORD in .env before
 * seeding a live install, and change the password after the first login.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@booktala.test')],
            [
                'name' => 'BookTala Admin',
                'password' => env('ADMIN_PASSWORD', 'Admin@1234'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('super_admin');
    }
}
