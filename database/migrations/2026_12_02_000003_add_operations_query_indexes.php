<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Composite indexes for the queries the dashboards, staff portal and the
 * 15-minute operations check run constantly: open work per person and per
 * property, unread guest messages, conversations by guest/property, and the
 * day's roster per site.
 */
return new class extends Migration
{
    /** table => [index name => columns] */
    private const INDEXES = [
        'tasks' => [
            'tasks_assigned_to_status_index' => ['assigned_to', 'status'],
            'tasks_property_id_status_index' => ['property_id', 'status'],
            'tasks_status_due_date_index' => ['status', 'due_date'],
        ],
        'maintenance_reports' => [
            'maintenance_reports_assigned_to_status_index' => ['assigned_to', 'status'],
            'maintenance_reports_property_id_status_index' => ['property_id', 'status'],
        ],
        'messages' => [
            'messages_conversation_id_read_index' => ['conversation_id', 'read'],
        ],
        'conversations' => [
            'conversations_user_id_last_message_at_index' => ['user_id', 'last_message_at'],
            'conversations_property_id_index' => ['property_id'],
        ],
        'staff_schedules' => [
            'staff_schedules_site_id_date_index' => ['site_id', 'date'],
            'staff_schedules_date_status_index' => ['date', 'status'],
        ],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($table, $indexes) {
                foreach ($indexes as $name => $columns) {
                    if (! Schema::hasIndex($table, $name)) {
                        $blueprint->index($columns, $name);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($table, $indexes) {
                foreach (array_keys($indexes) as $name) {
                    if (Schema::hasIndex($table, $name)) {
                        $blueprint->dropIndex($name);
                    }
                }
            });
        }
    }
};
