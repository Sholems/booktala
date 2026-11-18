<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 operations: shifts belong to a site, staff clock in and out against
 * the site's location, unstarted work escalates, and people get in-app alerts.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Where the site is, for checking clock-ins.
        Schema::table('sites', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('country');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->unsignedInteger('geofence_radius_m')->default(200)->after('longitude');
        });

        Schema::table('staff_schedules', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->after('staff_id')->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('missed_alerted_at')->nullable()->after('created_by');
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('clock_in_at');
            $table->decimal('clock_in_lat', 10, 7)->nullable();
            $table->decimal('clock_in_lng', 10, 7)->nullable();
            $table->unsignedInteger('clock_in_accuracy_m')->nullable();
            $table->unsignedInteger('clock_in_distance_m')->nullable();
            $table->string('clock_in_location', 10)->default('unknown'); // on_site | off_site | unknown
            $table->unsignedInteger('late_minutes')->default(0);
            $table->timestamp('clock_out_at')->nullable();
            $table->decimal('clock_out_lat', 10, 7)->nullable();
            $table->decimal('clock_out_lng', 10, 7)->nullable();
            $table->unsignedInteger('clock_out_distance_m')->nullable();
            $table->string('clock_out_location', 10)->nullable();
            $table->unsignedInteger('worked_minutes')->nullable();
            $table->text('handover_note')->nullable();
            $table->boolean('auto_closed')->default(false);
            $table->timestamps();

            $table->index(['site_id', 'clock_in_at']);
            $table->index(['staff_id', 'clock_out_at']);
        });

        // Escalation state, and free-text priority so "emergency" works on MySQL too.
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('priority', 20)->default('medium')->change();
            $table->timestamp('assigned_at')->nullable()->after('assigned_by');
            $table->timestamp('started_at')->nullable()->after('status');
            $table->unsignedTinyInteger('escalation_level')->default(0)->after('completed_at');
            $table->timestamp('escalated_at')->nullable()->after('escalation_level');
        });
        Schema::table('maintenance_reports', function (Blueprint $table) {
            $table->string('priority', 20)->default('medium')->change();
            $table->timestamp('assigned_at')->nullable()->after('status');
            $table->unsignedTinyInteger('escalation_level')->default(0)->after('resolved_at');
            $table->timestamp('escalated_at')->nullable()->after('escalation_level');
        });

        // Laravel's in-app notifications. Booking notifications already send to the
        // "database" channel, which failed without this table.
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        // Existing shifts belong to the staff member's primary site.
        $primary = DB::table('staff_assignments')
            ->where('is_primary', true)
            ->whereNull('ends_on')
            ->pluck('site_id', 'staff_id');
        foreach ($primary as $staffId => $siteId) {
            DB::table('staff_schedules')->where('staff_id', $staffId)->whereNull('site_id')->update(['site_id' => $siteId]);
        }
    }

    public function down(): void
    {
        Schema::table('maintenance_reports', function (Blueprint $table) {
            $table->dropColumn(['assigned_at', 'escalation_level', 'escalated_at']);
        });
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['assigned_at', 'started_at', 'escalation_level', 'escalated_at']);
        });
        Schema::dropIfExists('attendances');
        Schema::table('staff_schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('site_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn('missed_alerted_at');
        });
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'geofence_radius_m']);
        });
        // The notifications table is kept: booking notifications rely on it.
    }
};
