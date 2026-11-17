<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_spaces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('capacity')->default(0);
            $table->integer('size')->nullable();
            $table->decimal('hourly_rate', 12, 2)->nullable();
            $table->decimal('daily_rate', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('amenities')->nullable();
            $table->timestamps();
        });

        Schema::create('event_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_space_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('event_name');
            $table->string('event_type');
            $table->date('event_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('num_guests');
            $table->decimal('total_price', 12, 2);
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_bookings');
        Schema::dropIfExists('event_spaces');
    }
};