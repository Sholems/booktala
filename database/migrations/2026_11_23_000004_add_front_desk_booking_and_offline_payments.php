<?php

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('booking_source', 20)->default('public')->after('guest_id');
            $table->foreignId('created_by')->nullable()->after('booking_source')->constrained('users')->nullOnDelete();
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->string('provider', 20)->default('paystack')->after('guest_id');
            $table->string('reference')->nullable()->after('provider');
            $table->foreignId('collected_by')->nullable()->after('reference')->constrained('users')->nullOnDelete();
            $table->timestamp('collected_at')->nullable()->after('collected_by');
            $table->unique(['provider', 'reference']);
        });

        (new RolePermissionSeeder)->run();
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['provider', 'reference']);
            $table->dropConstrainedForeignId('collected_by');
            $table->dropColumn(['provider', 'reference', 'collected_at']);
        });
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn('booking_source');
        });
    }
};
