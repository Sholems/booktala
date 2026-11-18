<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\Site;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Test logins for each portal, for local development only.
 *
 *   php artisan db:seed --class=LocalTestAccountsSeeder
 *
 * Refuses to run in production. Not called by DatabaseSeeder.
 */
class LocalTestAccountsSeeder extends Seeder
{
    /** email => [name, role, password] */
    public const ACCOUNTS = [
        'admin.test@booktala.test' => ['Test Admin', 'admin', 'AdminTest#2026'],
        'staff.test@booktala.test' => ['Test Staff', 'staff', 'StaffTest#2026'],
        'guest.test@booktala.test' => ['Test Guest', 'customer', 'GuestTest#2026'],
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('LocalTestAccountsSeeder does not run in production.');

            return;
        }

        foreach (self::ACCOUNTS as $email => [$name, $role, $password]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => $password, 'status' => 'active', 'email_verified_at' => now()]
            );
            $user->syncRoles([$role]);

            if ($role === 'staff') {
                $staff = Staff::firstOrCreate(['user_id' => $user->id], [
                    'department' => 'frontdesk',
                    'employee_id' => 'WP-TEST-'.$user->id,
                    'hire_date' => today(),
                ]);

                // Receptionist at every site, so the demo login sees all demo data.
                $receptionist = Position::where('name', 'Receptionist')->first();
                if ($receptionist && ! $staff->activeAssignments()->exists()) {
                    foreach (Site::orderBy('id')->get() as $site) {
                        $staff->assignTo($site, $receptionist);
                    }
                }
            }

            $this->command?->info("{$role}: {$email}");
        }
    }
}
