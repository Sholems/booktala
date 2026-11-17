<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->timestamp('checked_in_at')->nullable()->after('confirmed_at');
            $table->foreignId('checked_in_by')->nullable()->after('checked_in_at')->constrained('users')->nullOnDelete();
            $table->timestamp('checked_out_at')->nullable()->after('checked_in_by');
            $table->foreignId('checked_out_by')->nullable()->after('checked_out_at')->constrained('users')->nullOnDelete();
            // null = nothing to refund, pending = paid booking cancelled and awaiting review, refunded, declined
            $table->string('refund_status', 20)->nullable()->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('checked_in_by');
            $table->dropConstrainedForeignId('checked_out_by');
            $table->dropColumn(['checked_in_at', 'checked_out_at', 'refund_status']);
        });
    }
};
