<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('loyalty_transactions')) {
            return;
        }

        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('guest_profile_id');
            $table->unsignedBigInteger('reservation_id')->nullable();
            $table->enum('type', ['earn', 'redeem', 'expire', 'bonus', 'adjust']);
            $table->integer('points');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index('guest_profile_id');
            $table->index('reservation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_transactions');
    }
};
