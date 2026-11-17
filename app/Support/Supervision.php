<?php

namespace App\Support;

use App\Models\StaffAssignment;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Who oversees which sites. Supervisors and managers are staff whose current
 * assignment at a site is in a supervisor- or manager-level position.
 */
class Supervision
{
    public const OVERSIGHT_LEVELS = ['supervisor', 'manager'];

    /**
     * Site ids this user supervises or manages today.
     *
     * @return Collection<int, int>
     */
    public static function siteIds(?User $user): Collection
    {
        if (!$user) {
            return collect();
        }

        return StaffAssignment::active()
            ->join('staff', 'staff.id', '=', 'staff_assignments.staff_id')
            ->join('positions', 'positions.id', '=', 'staff_assignments.position_id')
            ->where('staff.user_id', $user->id)
            ->where('staff.is_active', true)
            ->whereIn('positions.level', self::OVERSIGHT_LEVELS)
            ->distinct()
            ->pluck('staff_assignments.site_id');
    }

    public static function isSupervisor(?User $user): bool
    {
        return self::siteIds($user)->isNotEmpty();
    }

    public static function supervises(?User $user, ?int $siteId): bool
    {
        return $siteId !== null && self::siteIds($user)->contains($siteId);
    }

    /**
     * Active users holding the given levels at a site (for alerts).
     *
     * @param  array<int, string>  $levels
     * @return Collection<int, User>
     */
    public static function overseers(int $siteId, array $levels): Collection
    {
        return User::where('status', 'active')
            ->whereHas('staff', fn ($q) => $q->where('is_active', true)
                ->whereHas('assignments', fn ($a) => $a->active()
                    ->where('site_id', $siteId)
                    ->whereHas('position', fn ($p) => $p->whereIn('level', $levels))))
            ->get();
    }

    /**
     * Who to alert about a problem at a site, by escalation step:
     *  1 = site supervisors (or managers, then head office if there are none);
     *  2 = managers and head office.
     *
     * @return Collection<int, User>
     */
    public static function alertRecipients(int $siteId, int $step): Collection
    {
        $headOffice = fn () => User::role(['super_admin', 'admin'])->where('status', 'active')->get();

        if ($step <= 1) {
            $people = self::overseers($siteId, ['supervisor']);
            if ($people->isEmpty()) {
                $people = self::overseers($siteId, ['manager']);
            }

            return $people->isEmpty() ? $headOffice() : $people;
        }

        return self::overseers($siteId, ['manager'])->merge($headOffice())->unique('id')->values();
    }
}
