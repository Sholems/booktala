<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Illuminate\Console\Command;

class ExpireReservationHolds extends Command
{
    protected $signature = 'bookings:expire-holds';

    protected $description = 'Cancel unpaid reservation holds after their payment window closes';

    public function handle(): int
    {
        $expired = Reservation::query()
            ->where('booking_status', 'pending')
            ->where('payment_status', 'pending')
            ->where(function ($query) {
                $query->where('hold_expires_at', '<=', now())
                    ->orWhere(function ($query) {
                        $query->whereNull('hold_expires_at')
                            ->where('created_at', '<=', now()->subMinutes(config('booking.hold_minutes')));
                    });
            })
            ->update([
                'booking_status' => 'cancelled',
                'cancellation_reason' => 'Payment window expired',
                'cancelled_at' => now(),
                'hold_expires_at' => null,
            ]);

        $this->info("Expired {$expired} reservation hold(s).");

        return self::SUCCESS;
    }
}
