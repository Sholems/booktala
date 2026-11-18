<?php

namespace App\Policies;

use App\Models\Guest;
use App\Models\User;

class GuestPolicy
{
    public function view(User $user, Guest $guest): bool
    {
        return $guest->user_id === $user->id || $user->can('portfolio.view_all');
    }
}
