<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles and what each may do. Admin routes check these permissions
 * (super_admin is always allowed). Safe to run repeatedly; a migration
 * runs it on deploy so new permissions reach existing installs.
 */
class RolePermissionSeeder extends Seeder
{
    public const PERMISSIONS = [
        'portfolio.view_all',
        'property.view',
        'property.create',
        'property.edit',
        'property.delete',
        'reservation.view',
        'reservation.manage',
        'reservation.create',
        'reservation.refund',
        'reservation.checkin-unpaid',
        'customer.view',
        'customer.manage',
        'staff.manage',
        'owner.manage',
        'site.manage',
        'task.manage',
        'maintenance.manage',
        'review.manage',
        'page.manage',
        'settings.manage',
        'report.view',
        'report.export',
        'finance.manage',  // expenses and owner statements
        'payroll.manage',  // salaries and pay runs: super admin only by default
    ];

    public const ROLE_PERMISSIONS = [
        'admin' => [
            'portfolio.view_all',
            'property.view', 'property.create', 'property.edit',
            'reservation.view', 'reservation.manage', 'reservation.create',
            'reservation.refund', 'reservation.checkin-unpaid',
            'customer.view', 'customer.manage',
            'staff.manage', 'owner.manage', 'site.manage', 'task.manage', 'maintenance.manage',
            'review.manage', 'page.manage', 'settings.manage', 'report.view', 'report.export',
            'finance.manage',
        ],
        'staff' => [
            'reservation.create',
            'task.manage', 'maintenance.manage',
        ],
        'customer' => [],
        'owner' => [], // property owners: read-only portal for their own properties
    ];

    public function run(): void
    {
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web'])
            ->syncPermissions(self::PERMISSIONS);

        foreach (self::ROLE_PERMISSIONS as $roleName => $rolePermissions) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web'])
                ->syncPermissions($rolePermissions);
        }

        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
