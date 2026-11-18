<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Illuminate\Console\Command;

class AuditGuestMigration extends Command
{
    protected $signature = 'guests:audit-migration';

    protected $description = 'Report reservations that do not have a durable guest identity';

    public function handle(): int
    {
        $missing = Reservation::whereNull('guest_id')->count();
        $this->line('reservations missing guest: '.$missing);

        return $missing === 0 ? self::SUCCESS : self::FAILURE;
    }
}
