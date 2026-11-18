<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->decimal('processing_fee', 12, 2)->default(0)->after('tax_amount');
            $table->decimal('caution_fee', 12, 2)->default(0)->after('processing_fee');
            $table->string('caution_fee_status', 20)->nullable()->after('caution_fee');
            $table->timestamp('caution_refunded_at')->nullable()->after('caution_fee_status');
            $table->string('discount_code')->nullable()->after('discount_amount');
            $table->timestamp('hold_expires_at')->nullable()->index()->after('booking_status');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('processing_fee', 12, 2)->default(0)->after('vat_amount');
            $table->decimal('caution_fee', 12, 2)->default(0)->after('processing_fee');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['processing_fee', 'caution_fee']);
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex(['hold_expires_at']);
            $table->dropColumn([
                'processing_fee',
                'caution_fee',
                'caution_fee_status',
                'caution_refunded_at',
                'discount_code',
                'hold_expires_at',
            ]);
        });
    }
};
