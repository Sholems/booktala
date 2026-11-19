<?php

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Admins manage OTA channels through their own permission and no longer
     * get settings.manage, which guards payment and mail credentials.
     */
    public function up(): void
    {
        (new RolePermissionSeeder)->run();
    }

    public function down(): void
    {
        // Permissions are left in place; removing them could lock users out.
    }
};
