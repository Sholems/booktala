<?php

namespace App\Policies;

use App\Models\OwnerStatement;
use App\Models\User;
use App\Support\PropertyAccess;

class OwnerStatementPolicy
{
    public function view(User $user, OwnerStatement $statement): bool
    {
        return $statement->owner?->user_id === $user->id
            || ($user->can('finance.manage') && PropertyAccess::canAccessOwner($user, $statement->owner_id));
    }
}
