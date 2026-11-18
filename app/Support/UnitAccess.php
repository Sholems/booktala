<?php

namespace App\Support;

use App\Models\Unit;
use App\Models\User;

class UnitAccess
{
    public static function canAccess(?User $user, Unit|int|null $unit): bool
    {
        return PropertyAccess::canAccess($user, $unit);
    }

    public static function authorize(?User $user, Unit|int|null $unit): void
    {
        PropertyAccess::authorize($user, $unit);
    }
}
