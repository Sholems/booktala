<?php

namespace App\Services;

use Illuminate\Support\Collection;

final readonly class GuestMatch
{
    public function __construct(public Collection $guests) {}

    public function requiresSelection(): bool
    {
        return $this->guests->count() > 1;
    }
}
