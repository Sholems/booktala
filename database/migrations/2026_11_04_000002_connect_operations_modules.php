<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Schedules belong to staff profiles, but adding a staff user never created one,
        // so no shift could be scheduled. Give every existing staff user a profile.
        $staffRoleId = DB::table('roles')->where('name', 'staff')->value('id');
        if ($staffRoleId) {
            $staffUserIds = DB::table('model_has_roles')
                ->where('role_id', $staffRoleId)
                ->where('model_type', User::class)
                ->pluck('model_id');

            foreach ($staffUserIds as $userId) {
                if (! DB::table('staff')->where('user_id', $userId)->exists()) {
                    DB::table('staff')->insert([
                        'user_id' => $userId,
                        'department' => 'frontdesk',
                        'employee_id' => 'WP-'.str_pad((string) $userId, 4, '0', STR_PAD_LEFT),
                        'hire_date' => now()->toDateString(),
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // Room service is ordered against a booking; a specific room unit is optional.
        Schema::table('room_service_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('room_id')->nullable()->change();
            $table->foreignId('handled_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
        });

        // Maintenance can be assigned to a staff member to fix.
        Schema::table('maintenance_reports', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('reported_by')->constrained('users')->nullOnDelete();
        });

        // Front desk records who checked the guest's ID from the online check-in.
        Schema::table('guest_checkins', function (Blueprint $table) {
            $table->foreignId('verified_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
        });

        // Link a cleaning task back to the stay that created it, so it is only created once.
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('reservation_id')->nullable()->after('property_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reservation_id');
        });
        Schema::table('guest_checkins', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn('verified_at');
        });
        Schema::table('maintenance_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
        });
        Schema::table('room_service_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('handled_by');
        });
    }
};
