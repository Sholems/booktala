<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guests now write to the team inbox, so a guest's message has no single receiver.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('receiver_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Left nullable: older rows written to the team have no receiver to restore.
    }
};
