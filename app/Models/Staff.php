<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Staff extends Model
{
    protected $fillable = [
        'user_id', 'department', 'employee_id', 'hire_date', 'is_active',
        'bank_name', 'bank_account_name', 'bank_account_number', 'tax_id', 'pension_pin',
        'emergency_contact_name', 'emergency_contact_phone',
    ];

    public function salaryStructure(): HasOne
    {
        return $this->hasOne(SalaryStructure::class);
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(StaffDeduction::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    protected function casts(): array
    {
        return ['hire_date' => 'date', 'is_active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(StaffAssignment::class);
    }

    public function activeAssignments(): HasMany
    {
        return $this->assignments()->active();
    }

    public function activeSites()
    {
        return Site::whereIn('id', $this->activeAssignments()->pluck('site_id'));
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /** The shift they are clocked in to right now, if any. */
    public function openAttendance(): HasOne
    {
        return $this->hasOne(Attendance::class)->whereNull('clock_out_at')->latest('clock_in_at');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(StaffSchedule::class);
    }

    public function primaryAssignment(): HasOne
    {
        return $this->hasOne(StaffAssignment::class)->active()->orderByDesc('is_primary')->orderBy('id');
    }

    /**
     * Put this staff member to work at a site. Their department follows their
     * primary position so older screens that show it stay accurate.
     */
    public function assignTo(Site $site, Position $position, bool $primary = false, $startsOn = null): StaffAssignment
    {
        $primary = $primary || ! $this->activeAssignments()->exists();

        if ($primary) {
            $this->assignments()->update(['is_primary' => false]);
            $this->update(['department' => $position->department]);
        }

        return $this->assignments()->create([
            'site_id' => $site->id,
            'position_id' => $position->id,
            'is_primary' => $primary,
            'starts_on' => $startsOn ?? today(),
        ]);
    }

    public function isSupervisorAt(Site|int $site): bool
    {
        $siteId = $site instanceof Site ? $site->id : $site;

        return \App\Support\Supervision::supervises($this->user, $siteId);
    }
}
