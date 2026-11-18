<?php

namespace App\Policies;

use App\Models\OwnerStatement;
use App\Models\User;

class OwnerStatementPolicy
{
    public function view(User $user, OwnerStatement $statement): bool
    {
        return $statement->owner?->user_id === $user->id || $user->can('portfolio.view_all');
    }
}
