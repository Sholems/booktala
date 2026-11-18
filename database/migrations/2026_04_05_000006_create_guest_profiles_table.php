<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('total_stays')->default(0);
            $table->integer('total_nights')->default(0);
            $table->decimal('total_spent', 12, 2)->default(0);
            $table->integer('loyalty_points')->default(0);
            $table->string('loyalty_tier')->default('bronze');
            $table->date('last_stay_date')->nullable();
            $table->json('preferences')->nullable();
            $table->text('special_requests')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_profiles');
    }
};
