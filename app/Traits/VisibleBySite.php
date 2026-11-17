<?php

namespace App\Traits;

use App\Models\User;
use App\Support\PropertyAccess;

/**
 * Adds ->visibleTo($user) for records that belong to a site: head office sees
 * every site, staff see the sites they are currently assigned to.
 */
trait VisibleBySite
{
    public function scopeVisibleTo($query, User $user)
    {
        if (PropertyAccess::seesAll($user)) {
            return $query;
        }

        return $query->whereIn($this->qualifyColumn('site_id'), PropertyAccess::activeSiteIds($user));
    }
}
