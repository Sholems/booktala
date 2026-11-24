<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE properties DROP CONSTRAINT IF EXISTS properties_status_check');
        DB::statement(<<<'SQL'
            ALTER TABLE properties
            ADD CONSTRAINT properties_status_check
            CHECK (status IN ('active', 'inactive', 'maintenance', 'cleaning', 'available', 'occupied', 'reserved'))
        SQL);
    }

    public function down(): void
    {
        // Operational unit states cannot be collapsed safely to the old list.
    }
};
