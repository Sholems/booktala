<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;
use App\Support\PropertyAccess;
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
        return PropertyAccess::canAccess($user, $property);
    }

    public function create(User $user): bool
    {
        return $user->can('property.create');
    }

    public function update(User $user, Property $property): bool
    {
        return PropertyAccess::canAccess($user, $property) && $user->can('property.edit');
    }

    public function delete(User $user, Property $property): bool
    {
        return PropertyAccess::canAccess($user, $property) && $user->can('property.delete');
    }
}
