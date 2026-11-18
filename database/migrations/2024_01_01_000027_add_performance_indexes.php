<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->createIndexIfNotExists('properties', 'idx_properties_status', 'status');
        $this->createIndexIfNotExists('properties', 'idx_properties_is_featured', 'is_featured');
        $this->createIndexIfNotExists('properties', 'idx_properties_city', 'city');
        $this->createIndexIfNotExists('properties', 'idx_properties_price', 'price_per_night');
        $this->createIndexIfNotExists('properties', 'idx_properties_created_at', 'created_at');

        $this->createIndexIfNotExists('reservations', 'idx_reservations_property_id', 'property_id');
        $this->createIndexIfNotExists('reservations', 'idx_reservations_user_id', 'user_id');
        $this->createIndexIfNotExists('reservations', 'idx_reservations_booking_status', 'booking_status');
        $this->createIndexIfNotExists('reservations', 'idx_reservations_payment_status', 'payment_status');
        $this->createIndexIfNotExists('reservations', 'idx_reservations_check_in', 'check_in');
        $this->createIndexIfNotExists('reservations', 'idx_reservations_check_out', 'check_out');
        $this->createIndexIfNotExists('reservations', 'idx_reservations_created_at', 'created_at');

        $this->createIndexIfNotExists('reviews', 'idx_reviews_property_id', 'property_id');
        $this->createIndexIfNotExists('reviews', 'idx_reviews_user_id', 'user_id');
        $this->createIndexIfNotExists('reviews', 'idx_reviews_is_published', 'is_published');

        $this->createIndexIfNotExists('tasks', 'idx_tasks_property_id', 'property_id');
        $this->createIndexIfNotExists('tasks', 'idx_tasks_assigned_to', 'assigned_to');
        $this->createIndexIfNotExists('tasks', 'idx_tasks_status', 'status');

        $this->createIndexIfNotExists('blogs', 'idx_blogs_is_published', 'is_published');
        $this->createIndexIfNotExists('blogs', 'idx_blogs_published_at', 'published_at');
    }

    private function createIndexIfNotExists(string $table, string $index, string $column): void
    {
        try {
            $exists = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
            if (empty($exists)) {
                DB::statement("CREATE INDEX {$index} ON {$table}({$column})");
            }
        } catch (Exception $e) {
            // Index may already exist or table doesn't exist
        }
    }

    public function down(): void
    {
        $indexes = [
            'properties' => ['idx_properties_status', 'idx_properties_is_featured', 'idx_properties_city', 'idx_properties_price', 'idx_properties_created_at'],
            'reservations' => ['idx_reservations_property_id', 'idx_reservations_user_id', 'idx_reservations_booking_status', 'idx_reservations_payment_status', 'idx_reservations_check_in', 'idx_reservations_check_out', 'idx_reservations_created_at'],
            'reviews' => ['idx_reviews_property_id', 'idx_reviews_user_id', 'idx_reviews_is_published'],
            'tasks' => ['idx_tasks_property_id', 'idx_tasks_assigned_to', 'idx_tasks_status'],
            'blogs' => ['idx_blogs_is_published', 'idx_blogs_published_at'],
        ];

        foreach ($indexes as $table => $indexNames) {
            foreach ($indexNames as $index) {
                try {
                    DB::statement("DROP INDEX {$index} ON {$table}");
                } catch (Exception $e) {
                    // Index may not exist
                }
            }
        }
    }
};
