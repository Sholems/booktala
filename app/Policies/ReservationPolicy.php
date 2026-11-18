<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;
use App\Support\PropertyAccess;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReservationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('reservation.view') || $user->hasRole('staff');
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return $reservation->user_id === $user->id
            || (PropertyAccess::canAccess($user, $reservation->property_id) && $user->can('reservation.view'));
    }

    public function update(User $user, Reservation $reservation): bool
    {
        return PropertyAccess::canAccess($user, $reservation->property_id) && $user->can('reservation.manage');
    }

    public function cancel(User $user, Reservation $reservation): bool
    {
        return $reservation->user_id === $user->id
            || (PropertyAccess::canAccess($user, $reservation->property_id) && $user->can('reservation.manage'));
    }
}
