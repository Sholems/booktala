<?php

namespace App\Services;

use App\Models\Guest;
use App\Models\User;
use App\Support\PropertyAccess;

class GuestService
{
    public static function normalizeEmail(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));

        return $email !== '' ? $email : null;
    }

    public static function normalizePhone(?string $phone): ?string
    {
        $phone = preg_replace('/\D+/', '', (string) $phone);

        return $phone !== '' ? $phone : null;
    }

    public function match(array $contact): GuestMatch
    {
        $email = self::normalizeEmail($contact['email'] ?? null);
        $phone = self::normalizePhone($contact['phone'] ?? null);

        $guests = Guest::query()
            ->where(function ($query) use ($email, $phone) {
                if ($email) {
                    $query->orWhere('normalized_email', $email);
                }
                if ($phone) {
                    $query->orWhere('normalized_phone', $phone);
                }
            })
            ->orderBy('id')
            ->get();

        return new GuestMatch($guests);
    }

    public function matchForStaff(User $actor, array $contact): GuestMatch
    {
        $match = $this->match($contact);
        if (PropertyAccess::seesAll($actor)) {
            return $match;
        }

        $visibleGuests = $match->guests->filter(fn (Guest $guest) => $guest->reservations()
            ->visibleTo($actor)
            ->exists())
            ->values();

        return new GuestMatch($visibleGuests);
    }

    public function findForStaff(User $actor, int $guestId): ?Guest
    {
        $guest = Guest::find($guestId);
        if (! $guest || (! PropertyAccess::seesAll($actor) && ! $guest->reservations()->visibleTo($actor)->exists())) {
            return null;
        }

        return $guest;
    }

    public function linkUser(Guest $guest, User $user): Guest
    {
        $guest->update([
            'user_id' => $user->id,
            'name' => $guest->name ?: $user->name,
            'email' => $guest->email ?: $user->email,
            'normalized_email' => $guest->normalized_email ?: self::normalizeEmail($user->email),
            'phone' => $guest->phone ?: $user->phone,
            'normalized_phone' => $guest->normalized_phone ?: self::normalizePhone($user->phone),
        ]);

        return $guest->fresh();
    }
}
