<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReservationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('staff');
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return $user->hasRole('admin') ||
               $user->hasRole('staff') ||
               $reservation->user_id === $user->id;
    }

    public function update(User $user, Reservation $reservation): bool
    {
        return $user->hasRole('admin') || $user->hasRole('staff');
    }

    public function cancel(User $user, Reservation $reservation): bool
    {
        return $user->hasRole('admin') ||
               $user->hasRole('staff') ||
               $reservation->user_id === $user->id;
    }
}
