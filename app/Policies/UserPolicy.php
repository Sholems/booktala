<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, User $target): bool
    {
        return $user->hasRole('admin') || $user->id === $target->id;
    }

    public function update(User $user, User $target): bool
    {
        return $user->hasRole('admin') || $user->id === $target->id;
    }

    public function delete(User $user, User $target): bool
    {
        return $user->hasRole('admin') && $user->id !== $target->id;
    }
}