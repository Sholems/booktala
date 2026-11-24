<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL keeps the original enum CHECK after Laravel changes the
        // column to varchar. Positions now legitimately define more departments.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE staff DROP CONSTRAINT IF EXISTS staff_department_check');
        }
    }

    public function down(): void
    {
        // Restoring the obsolete three-value constraint would reject valid data.
    }
};
