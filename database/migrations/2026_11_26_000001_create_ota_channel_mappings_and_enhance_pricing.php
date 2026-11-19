<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. OTA Channel Mappings for Two-Way Synchronization
        Schema::create('ota_channel_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->string('channel', 30); // airbnb, bookingcom, vrbo, expedia, other
            $table->string('export_token', 64)->unique();
            $table->text('import_ical_url')->nullable();
            $table->decimal('rate_multiplier', 4, 2)->default(1.00); // e.g. 1.15 = +15%
            $table->unsignedSmallInteger('min_stay_override')->nullable();
            $table->string('sync_status', 20)->default('idle'); // idle, syncing, success, error, conflict
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('conflict_details')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['property_id', 'channel']);
            $table->index(['channel', 'is_active']);
        });

        // 2. Enhance Pricing Rules for Dynamic Pricing & Seasonal Rates
        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->after('property_id')->constrained('sites')->nullOnDelete();
            $table->string('adjustment_type', 30)->default('fixed_override')->after('type'); // fixed_override, percentage_markup, percentage_discount, fixed_markup
            $table->decimal('adjustment_value', 12, 2)->nullable()->after('adjustment_type');
            $table->unsignedSmallInteger('min_nights')->nullable()->after('adjustment_value');
            $table->json('days_of_week')->nullable()->after('min_nights'); // e.g. [5,6] for weekend
            $table->integer('priority')->default(0)->after('days_of_week');
        });
    }

    public function down(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
            $table->dropColumn(['site_id', 'adjustment_type', 'adjustment_value', 'min_nights', 'days_of_week', 'priority']);
        });

        Schema::dropIfExists('ota_channel_mappings');
    }
};
