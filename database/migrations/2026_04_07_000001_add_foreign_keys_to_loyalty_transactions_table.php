<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        if (! Schema::hasTable('loyalty_transactions')) {
            return;
        }

        if (Schema::hasTable('guest_profiles') && ! $this->hasForeignKey('loyalty_transactions_guest_profile_id_foreign')) {
            Schema::table('loyalty_transactions', function (Blueprint $table) {
                $table->foreign('guest_profile_id')
                    ->references('id')
                    ->on('guest_profiles')
                    ->onDelete('cascade');
            });
        }

        if (Schema::hasTable('reservations') && ! $this->hasForeignKey('loyalty_transactions_reservation_id_foreign')) {
            Schema::table('loyalty_transactions', function (Blueprint $table) {
                $table->foreign('reservation_id')
                    ->references('id')
                    ->on('reservations')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        if (! Schema::hasTable('loyalty_transactions')) {
            return;
        }

        Schema::table('loyalty_transactions', function (Blueprint $table) {
            if ($this->hasForeignKey('loyalty_transactions_guest_profile_id_foreign')) {
                $table->dropForeign('loyalty_transactions_guest_profile_id_foreign');
            }
            if ($this->hasForeignKey('loyalty_transactions_reservation_id_foreign')) {
                $table->dropForeign('loyalty_transactions_reservation_id_foreign');
            }
        });
    }

    private function hasForeignKey(string $constraintName): bool
    {
        $driver = DB::getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return false;
        }

        $database = DB::getDatabaseName();

        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', 'loyalty_transactions')
            ->where('CONSTRAINT_NAME', $constraintName)
            ->exists();
    }
};
