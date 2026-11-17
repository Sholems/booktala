<?php

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * New owner.manage and site.manage permissions for the multi-property screens.
     */
    public function up(): void
    {
        (new RolePermissionSeeder())->run();
    }

    public function down(): void
    {
        // Permissions are left in place; removing them would lock admins out.
    }
};
