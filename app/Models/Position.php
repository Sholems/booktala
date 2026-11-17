<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A job a staff member does at a site, e.g. Receptionist or Security Guard.
 * Admins can add positions; the department groups them, the level marks
 * supervisors and managers.
 */
class Position extends Model
{
    public const DEPARTMENTS = [
        'frontdesk' => 'Front desk',
        'housekeeping' => 'Housekeeping',
        'laundry' => 'Laundry',
        'maintenance' => 'Maintenance',
        'security' => 'Security',
        'management' => 'Management',
    ];

    public const LEVELS = [
        'staff' => 'Staff',
        'supervisor' => 'Supervisor',
        'manager' => 'Manager',
    ];

    protected $fillable = ['name', 'department', 'level', 'description'];

    public function assignments(): HasMany
    {
        return $this->hasMany(StaffAssignment::class);
    }

    public function departmentLabel(): string
    {
        return self::DEPARTMENTS[$this->department] ?? ucfirst($this->department);
    }

    public function levelLabel(): string
    {
        return self::LEVELS[$this->level] ?? ucfirst($this->level);
    }
}
