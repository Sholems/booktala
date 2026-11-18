<?php

namespace App\Support;

use App\Models\Property;
use App\Models\StaffAssignment;
use App\Models\User;
use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * The single rule for which properties a person may see and act on in the
 * staff and admin portals:
 *
 *  - super admins and admins (head office): every property;
 *  - staff, supervisors and managers: properties at the sites they are
 *    currently assigned to;
 *  - anyone else: none.
 *
 * Every staff-facing query and record check goes through here, so a new screen
 * cannot leak another site's guests, tasks or reports by forgetting a filter.
 */
class PropertyAccess
{
    public static function seesAll(?User $user): bool
    {
        return $user !== null && $user->hasAnyRole(['super_admin', 'admin']);
    }

    /**
     * Restrict a query to rows whose property the user may see.
     *
     * @template T of BuilderContract
     *
     * @param  T  $query
     * @return T
     */
    public static function scope(BuilderContract $query, User $user, string $column = 'property_id'): BuilderContract
    {
        if (self::seesAll($user)) {
            return $query;
        }

        return $query->whereIn($column, self::visiblePropertyIds($user));
    }

    public static function canAccess(?User $user, Property|int|null $property): bool
    {
        if ($user === null || $property === null) {
            return self::seesAll($user);
        }

        if (self::seesAll($user)) {
            return true;
        }

        $id = $property instanceof Property ? $property->id : $property;

        return self::visiblePropertyIds($user)->where('properties.id', $id)->exists();
    }

    /**
     * Abort with 404 (not 403, so other sites' records are not even confirmed to exist).
     */
    public static function authorize(?User $user, Property|int|null $property): void
    {
        abort_unless(self::canAccess($user, $property), 404);
    }

    /**
     * Sub-select of property ids at the user's active sites.
     */
    public static function visiblePropertyIds(User $user): QueryBuilder
    {
        return Property::query()->toBase()
            ->select('properties.id')
            ->whereIn('properties.site_id', self::activeSiteIds($user));
    }

    /**
     * Sub-select of the site ids the user is currently assigned to.
     */
    public static function activeSiteIds(User $user): QueryBuilder
    {
        return StaffAssignment::query()->active()->toBase()
            ->select('staff_assignments.site_id')
            ->join('staff', 'staff.id', '=', 'staff_assignments.staff_id')
            ->where('staff.user_id', $user->id)
            ->where('staff.is_active', true);
    }

    /**
     * Users who work at the property's site today (for assigning its tasks and reports).
     */
    public static function staffAtProperty(Property|int $property): Builder
    {
        $siteId = $property instanceof Property ? $property->site_id : Property::whereKey($property)->value('site_id');

        return User::role('staff')
            ->where('status', 'active')
            ->whereHas('staff', fn ($q) => $q->where('is_active', true)
                ->whereHas('assignments', fn ($a) => $a->active()->where('site_id', $siteId)));
    }
}
