<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Notifications\BookingCheckinReminder;
use Illuminate\Console\Command;

class SendCheckinReminders extends Command
{
    protected $signature = 'bookings:send-checkin-reminders';
    protected $description = 'Send check-in reminder notifications to guests';

    public function handle()
    {
        $this->info('Sending check-in reminders...');
        
        $reservations = Reservation::where('booking_status', 'confirmed')
            ->whereDate('check_in', '=', now()->addDay()->toDateString())
            ->with(['user', 'property'])
            ->get();
        
        $count = 0;
        foreach ($reservations as $reservation) {
            if ($reservation->user) {
                $reservation->user->notify(new BookingCheckinReminder($reservation));
                $count++;
            }
        }
        
        $this->info("Sent {$count} check-in reminder(s).");
        
        return 0;
    }
}