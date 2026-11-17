<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_settings', function (Blueprint $table) {
            $table->id();
            $table->string('channel_name');
            $table->boolean('is_enabled')->default(false);
            $table->text('api_key')->nullable();
            $table->text('api_secret')->nullable();
            $table->foreignId('property_id')->nullable()->constrained()->onDelete('set null');
            $table->string('sync_frequency')->default('hourly');
            $table->timestamp('last_sync')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique('channel_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_settings');
    }
};