<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;
use App\Support\UnitAccess;

class UnitPolicy
{
    public function view(User $user, Unit $unit): bool
    {
        return UnitAccess::canAccess($user, $unit);
    }

    public function update(User $user, Unit $unit): bool
    {
        return UnitAccess::canAccess($user, $unit) && $user->can('property.edit');
    }
}
