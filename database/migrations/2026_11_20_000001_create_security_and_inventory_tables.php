<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4: site security (visitors, incidents, patrols) and site inventory.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete(); // flat being visited
            $table->string('visitor_name');
            $table->string('phone', 30)->nullable();
            $table->string('purpose', 30); // guest_visit | delivery | contractor | staff | viewing | other
            $table->string('host')->nullable(); // who they came to see
            $table->string('vehicle_plate', 20)->nullable();
            $table->string('id_type', 30)->nullable(); // which ID was shown; the number is not stored
            $table->timestamp('arrived_at');
            $table->timestamp('left_at')->nullable();
            $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('signed_out_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'left_at']);
            $table->index(['site_id', 'arrived_at']);
        });

        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->string('category', 20);
            $table->string('severity', 10); // low | medium | high | critical
            $table->string('title');
            $table->text('description');
            $table->text('actions_taken')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('status', 10)->default('open'); // open | resolved
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'status']);
        });

        Schema::table('sites', function (Blueprint $table) {
            // How often each checkpoint should be visited while patrolling; null = no patrols.
            $table->unsignedSmallInteger('patrol_interval_minutes')->nullable()->after('geofence_radius_m');
            $table->time('patrol_from')->nullable()->after('patrol_interval_minutes');
            $table->time('patrol_until')->nullable()->after('patrol_from');
        });

        Schema::create('patrol_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('token', 40)->unique(); // in the printed QR code
            $table->boolean('is_active')->default(true);
            $table->timestamp('missed_alerted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('patrol_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patrol_checkpoint_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->timestamp('scanned_at');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['patrol_checkpoint_id', 'scanned_at']);
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category', 20);
            $table->string('unit', 20)->default('pcs');
            $table->decimal('on_hand', 12, 2)->default(0);
            $table->decimal('at_laundry', 12, 2)->default(0);
            $table->decimal('reorder_level', 12, 2)->default(0);
            $table->boolean('tracks_laundry')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('low_stock_alerted_at')->nullable();
            $table->timestamps();

            $table->unique(['site_id', 'name']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // received | used | to_laundry | from_laundry | discarded | count
            $table->decimal('quantity', 12, 2); // always positive; the type says which way
            $table->decimal('on_hand_after', 12, 2);
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->string('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['inventory_item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('patrol_scans');
        Schema::dropIfExists('patrol_checkpoints');
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['patrol_interval_minutes', 'patrol_from', 'patrol_until']);
        });
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('visitor_logs');
    }
};
