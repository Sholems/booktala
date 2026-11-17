<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Property;
use Illuminate\Auth\Access\HandlesAuthorization;

class PropertyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Property $property): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Property $property): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Property $property): bool
    {
        return $user->hasRole('admin');
    }
}