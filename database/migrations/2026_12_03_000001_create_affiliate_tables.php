<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('code', 32)->unique();
            $table->decimal('commission_rate', 5, 2)->default(5.00);
            $table->string('bank_code', 10)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('account_number', 20)->nullable();
            $table->string('account_name', 150)->nullable();
            $table->string('recipient_code', 100)->nullable();
            $table->string('status', 20)->default('active'); // active, pending, suspended
            $table->decimal('lifetime_earnings', 12, 2)->default(0.00);
            $table->decimal('current_balance', 12, 2)->default(0.00);
            $table->timestamps();

            $table->index('code');
            $table->index('status');
        });

        Schema::create('affiliate_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->onDelete('cascade');
            $table->foreignId('reservation_id')->constrained('reservations')->onDelete('cascade');
            $table->foreignId('guest_user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('base_accommodation_amount', 12, 2);
            $table->decimal('commission_rate', 5, 2)->default(5.00);
            $table->decimal('commission_amount', 12, 2);
            $table->string('status', 20)->default('pending'); // pending, approved, paid, void
            $table->date('check_in');
            $table->date('check_out');
            $table->timestamp('matured_at')->nullable();
            $table->string('void_reason')->nullable();
            $table->timestamps();

            $table->index(['affiliate_id', 'status']);
            $table->index('matured_at');
        });

        Schema::create('affiliate_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->onDelete('cascade');
            $table->string('payout_ref', 50)->unique();
            $table->decimal('amount', 12, 2);
            $table->string('bank_name', 100);
            $table->string('account_number', 20);
            $table->string('account_name', 150);
            $table->string('status', 20)->default('requested'); // requested, processing, completed, failed
            $table->string('transfer_reference', 100)->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['affiliate_id', 'status']);
        });

        Schema::create('affiliate_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->onDelete('cascade');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('landing_page')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            $table->index('affiliate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_clicks');
        Schema::dropIfExists('affiliate_payouts');
        Schema::dropIfExists('affiliate_referrals');
        Schema::dropIfExists('affiliates');
    }
};
