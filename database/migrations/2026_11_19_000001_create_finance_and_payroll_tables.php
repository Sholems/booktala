<?php

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3: owner agreements and statements, property expenses, and payroll.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The management agreement and where payouts go. Statements snapshot the rate.
        Schema::table('owners', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->default(20)->after('is_company_owned');
            $table->boolean('deduct_expenses')->default(true)->after('commission_rate');
            $table->string('bank_name')->nullable()->after('deduct_expenses');
            $table->string('bank_account_name')->nullable()->after('bank_name');
            $table->string('bank_account_number', 20)->nullable()->after('bank_account_name');
        });

        Schema::create('owner_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('gross_revenue', 14, 2)->default(0);
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('commission_amount', 14, 2)->default(0);
            $table->decimal('expenses_total', 14, 2)->default(0);
            $table->decimal('net_payout', 14, 2)->default(0);
            $table->unsignedInteger('nights_booked')->default(0);
            $table->string('status', 10)->default('draft'); // draft | approved | paid
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['owner_id', 'period_start']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('category', 30);
            $table->string('description');
            $table->decimal('amount', 14, 2);
            $table->date('spent_on');
            $table->string('charge_to', 10)->default('owner'); // owner | company
            $table->foreignId('maintenance_report_id')->nullable()->constrained()->nullOnDelete();
            $table->string('receipt_path')->nullable();
            $table->foreignId('owner_statement_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['property_id', 'spent_on']);
        });

        Schema::create('owner_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_statement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 10); // booking | expense
            $table->unsignedBigInteger('source_id')->nullable();
            $table->date('date');
            $table->string('description');
            $table->unsignedInteger('nights')->nullable();
            $table->decimal('amount', 14, 2);
        });

        // Payroll: who gets paid what, and into which account.
        Schema::table('staff', function (Blueprint $table) {
            $table->string('bank_name')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number', 20)->nullable();
            $table->string('tax_id', 30)->nullable();
            $table->string('pension_pin', 30)->nullable();
        });

        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->unique()->constrained('staff')->cascadeOnDelete();
            $table->decimal('basic', 14, 2)->default(0);
            $table->decimal('housing', 14, 2)->default(0);
            $table->decimal('transport', 14, 2)->default(0);
            $table->decimal('other_allowance', 14, 2)->default(0);
            $table->decimal('overtime_rate', 10, 2)->nullable(); // per hour; blank = no overtime pay
            $table->decimal('annual_rent', 14, 2)->nullable(); // for rent relief
            $table->boolean('pension_enabled')->default(true);
            $table->boolean('nhf_enabled')->default(false);
            $table->boolean('deduct_absences')->default(true);
            $table->timestamps();
        });

        Schema::create('staff_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->string('type', 10); // loan | advance | other
            $table->string('description');
            $table->decimal('monthly_amount', 14, 2);
            $table->decimal('balance', 14, 2)->nullable(); // null = recurring with no end
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pay_runs', function (Blueprint $table) {
            $table->id();
            $table->date('period_start')->unique();
            $table->date('period_end');
            $table->string('status', 10)->default('draft'); // draft | approved | paid
            $table->decimal('total_gross', 16, 2)->default(0);
            $table->decimal('total_deductions', 16, 2)->default(0);
            $table->decimal('total_net', 16, 2)->default(0);
            $table->decimal('total_employer_pension', 16, 2)->default(0);
            $table->json('settings_snapshot')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pay_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->decimal('basic', 14, 2)->default(0);
            $table->decimal('housing', 14, 2)->default(0);
            $table->decimal('transport', 14, 2)->default(0);
            $table->decimal('other_allowance', 14, 2)->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('overtime_pay', 14, 2)->default(0);
            $table->unsignedSmallInteger('absent_days')->default(0);
            $table->decimal('absence_deduction', 14, 2)->default(0);
            $table->decimal('gross', 14, 2)->default(0);
            $table->decimal('pension_employee', 14, 2)->default(0);
            $table->decimal('pension_employer', 14, 2)->default(0);
            $table->decimal('nhf', 14, 2)->default(0);
            $table->decimal('paye', 14, 2)->default(0);
            $table->decimal('other_deductions', 14, 2)->default(0);
            $table->decimal('net', 14, 2)->default(0);
            $table->json('details')->nullable(); // deduction lines, site cost split, hours
            $table->timestamps();

            $table->unique(['pay_run_id', 'staff_id']);
        });

        (new RolePermissionSeeder())->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('pay_runs');
        Schema::dropIfExists('staff_deductions');
        Schema::dropIfExists('salary_structures');
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'bank_account_name', 'bank_account_number', 'tax_id', 'pension_pin']);
        });
        Schema::dropIfExists('owner_statement_lines');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('owner_statements');
        Schema::table('owners', function (Blueprint $table) {
            $table->dropColumn(['commission_rate', 'deduct_expenses', 'bank_name', 'bank_account_name', 'bank_account_number']);
        });
    }
};
