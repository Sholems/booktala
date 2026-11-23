<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Live Site Team Operational Messages ("Site Radio")
        if (! Schema::hasTable('site_messages')) {
            Schema::create('site_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('unit_id')->nullable()->constrained('properties')->nullOnDelete();
                $table->string('category', 30)->default('general'); // general, urgent, maintenance, guest_request, handover, security
                $table->text('message');
                $table->boolean('pinned')->default(false);
                $table->timestamp('pinned_at')->nullable();
                $table->foreignId('pinned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['site_id', 'created_at']);
                $table->index(['site_id', 'category']);
                $table->index(['site_id', 'pinned']);
            });
        }

        // 2. Handover Acknowledgement Tracking on Attendances
        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'handover_acknowledged_by')) {
                $table->foreignId('handover_acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('handover_acknowledged_at')->nullable();
            }
        });

        // 3. Conversation Classification (Guest vs Owner vs Internal)
        Schema::table('conversations', function (Blueprint $table) {
            if (! Schema::hasColumn('conversations', 'type')) {
                $table->string('type', 20)->default('guest'); // guest, owner, team
                $table->index('type');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_messages');

        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'handover_acknowledged_by')) {
                $table->dropForeign(['handover_acknowledged_by']);
                $table->dropColumn(['handover_acknowledged_by', 'handover_acknowledged_at']);
            }
        });

        Schema::table('conversations', function (Blueprint $table) {
            if (Schema::hasColumn('conversations', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
