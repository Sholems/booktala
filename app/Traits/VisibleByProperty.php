<?php

namespace App\Traits;

use App\Models\User;
use App\Support\PropertyAccess;

/**
 * Adds ->visibleTo($user) for models that belong to a property, using the
 * shared PropertyAccess rule. Rows without a property are head-office only.
 */
trait VisibleByProperty
{
    public function scopeVisibleTo($query, User $user)
    {
        return PropertyAccess::scope($query, $user, $this->qualifyColumn('property_id'));
    }
}
