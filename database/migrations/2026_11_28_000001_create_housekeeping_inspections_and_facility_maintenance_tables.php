<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Housekeeping Inspection Checklist & Verification
        Schema::create('housekeeping_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->foreignId('unit_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignId('cleaner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('pending_review'); // pending_review, approved, rejected
            $table->json('checklist')->nullable();
            $table->json('before_photos')->nullable();
            $table->json('after_photos')->nullable();
            $table->text('cleaner_notes')->nullable();
            $table->text('supervisor_notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['unit_id', 'status']);
            $table->index(['cleaner_id', 'status']);
            $table->index('status');
        });

        // 2. Preventative Facilities Maintenance Schedules (Generator, AC, Plumbing, Fumigation, etc.)
        Schema::create('facility_maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained('properties')->nullOnDelete();
            $table->string('equipment_type', 40); // generator, ac, plumbing, fumigation, inverter, borehole
            $table->string('title');
            $table->integer('frequency_days')->default(30);
            $table->date('last_serviced_on')->nullable();
            $table->date('next_due_on');
            $table->string('vendor_or_staff')->nullable();
            $table->decimal('estimated_cost', 12, 2)->default(0);
            $table->string('status', 20)->default('active'); // active, completed, paused
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'equipment_type']);
            $table->index(['next_due_on', 'status']);
        });

        // 3. Diesel Generator Run-Hours & Fuel Replenishment Tracking
        Schema::create('generator_fuel_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('generator_name', 100)->default('Main Standby Generator');
            $table->decimal('run_hours_start', 10, 2)->default(0);
            $table->decimal('run_hours_end', 10, 2)->default(0);
            $table->decimal('hours_run', 10, 2)->default(0);
            $table->decimal('litres_added', 10, 2)->default(0);
            $table->decimal('diesel_cost', 12, 2)->default(0);
            $table->integer('fuel_level_before_percent')->nullable();
            $table->integer('fuel_level_after_percent')->nullable();
            $table->foreignId('logged_by')->constrained('users')->cascadeOnDelete();
            $table->dateTime('logged_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'logged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generator_fuel_logs');
        Schema::dropIfExists('facility_maintenance_schedules');
        Schema::dropIfExists('housekeeping_inspections');
    }
};
