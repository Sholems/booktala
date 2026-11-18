<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained()->onDelete('cascade');
            $table->string('room_number');
            $table->integer('floor')->default(1);
            $table->string('building')->nullable();
            $table->enum('status', ['available', 'occupied', 'reserved', 'maintenance', 'cleaning'])->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['room_type_id', 'room_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
