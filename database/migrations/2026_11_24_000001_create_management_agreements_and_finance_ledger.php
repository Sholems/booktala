<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('management_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('properties')->restrictOnDelete();
            $table->foreignId('owner_id')->constrained()->restrictOnDelete();
            $table->decimal('commission_rate', 5, 2);
            $table->boolean('deduct_expenses')->default(true);
            $table->string('caution_forfeiture_recipient', 20)->default('company');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['unit_id', 'status', 'effective_from', 'effective_to'], 'agreements_unit_effective_index');
            $table->index(['owner_id', 'effective_from']);
        });

        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->ulid('reference')->unique();
            $table->string('idempotency_key')->unique();
            $table->char('payload_hash', 64);
            $table->string('source_type', 50);
            $table->string('source_id')->nullable();
            $table->string('event_type', 50);
            $table->char('currency', 3)->default('NGN');
            $table->timestamp('occurred_at');
            $table->text('description')->nullable();
            $table->foreignId('reversal_of_id')->nullable()->unique()->constrained('finance_transactions')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index(['event_type', 'occurred_at']);
        });

        Schema::create('finance_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_transaction_id')->constrained()->restrictOnDelete();
            $table->string('account', 50);
            $table->bigInteger('amount_minor');
            $table->foreignId('owner_id')->constrained()->restrictOnDelete();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('properties')->restrictOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('management_agreement_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('occurred_on');
            $table->timestamps();

            $table->index(['owner_id', 'occurred_on']);
            $table->index(['site_id', 'occurred_on']);
            $table->index(['unit_id', 'occurred_on']);
            $table->index(['account', 'occurred_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_entries');
        Schema::dropIfExists('finance_transactions');
        Schema::dropIfExists('management_agreements');
    }
};
