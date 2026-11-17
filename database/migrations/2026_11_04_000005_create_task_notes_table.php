<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Task notes become a thread between staff and admin, one row per message.
     */
    public function up(): void
    {
        Schema::create('task_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        // Keep notes written before the thread existed: each task's old log becomes its first entry.
        DB::table('tasks')->whereNotNull('notes')->where('notes', '!=', '')->orderBy('id')
            ->each(function ($task) {
                DB::table('task_notes')->insert([
                    'task_id' => $task->id,
                    'user_id' => null,
                    'body' => $task->notes,
                    'created_at' => $task->updated_at ?? now(),
                    'updated_at' => $task->updated_at ?? now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_notes');
    }
};
