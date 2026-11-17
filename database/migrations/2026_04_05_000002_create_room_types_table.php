<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('short_description')->nullable();
            $table->decimal('base_price', 12, 2);
            $table->decimal('weekend_price', 12, 2)->nullable();
            $table->decimal('price_per_week', 12, 2)->nullable();
            $table->decimal('price_per_month', 12, 2)->nullable();
            $table->integer('max_adults')->default(2);
            $table->integer('max_children')->default(1);
            $table->integer('total_rooms')->default(1);
            $table->string('bed_type')->nullable();
            $table->integer('room_size')->nullable();
            $table->json('amenities')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};