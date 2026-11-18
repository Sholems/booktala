<?php

namespace App\Services;

use App\Models\Guest;
use App\Models\User;

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
