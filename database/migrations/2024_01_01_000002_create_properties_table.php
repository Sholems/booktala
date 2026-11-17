<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('short_description')->nullable();
            $table->string('address');
            $table->string('city');
            $table->string('state');
            $table->string('country')->default('Nigeria');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('price_per_night', 12, 2);
            $table->decimal('weekend_price', 12, 2)->nullable();
            $table->decimal('price_per_week', 12, 2)->nullable();
            $table->decimal('price_per_month', 12, 2)->nullable();
            $table->integer('min_nights')->default(1);
            $table->integer('max_nights')->default(30);
            $table->integer('max_guests');
            $table->integer('bedrooms');
            $table->integer('bathrooms');
            $table->integer('toilets');
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->boolean('is_featured')->default(false);
            $table->time('check_in_time')->default('14:00:00');
            $table->time('check_out_time')->default('12:00:00');
            $table->text('house_rules')->nullable();
            $table->text('cancellation_policy')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};