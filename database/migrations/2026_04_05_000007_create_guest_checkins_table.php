<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->onDelete('cascade');
            $table->date('check_in_date');
            $table->time('check_in_time')->nullable();
            $table->string('guest_first_name');
            $table->string('guest_last_name');
            $table->string('guest_email');
            $table->string('guest_phone');
            $table->string('arrival_flight')->nullable();
            $table->string('arrival_time')->nullable();
            $table->text('special_requests')->nullable();
            $table->enum('id_type', ['passport', 'national_id', 'drivers_license', 'voters_card', 'other']);
            $table->string('id_number');
            $table->string('id_document_path')->nullable();
            $table->integer('num_adults')->default(1);
            $table->integer('num_children')->default(0);
            $table->string('vehicle_plate')->nullable();
            $table->string('emergency_contact_name');
            $table->string('emergency_contact_phone');
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_checkins');
    }
};
