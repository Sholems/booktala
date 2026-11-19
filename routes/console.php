<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('bookings:send-checkin-reminders')->dailyAt('8am');

// Escalate unstarted work, report missed shifts, close forgotten clock-ins.
Schedule::command('operations:check')->everyFifteenMinutes()->withoutOverlapping();

Schedule::command('bookings:expire-holds')->everyMinute()->withoutOverlapping();

// Two-way synchronization with Airbnb, Booking.com, VRBO calendars
Schedule::command('ota:sync')->everyFifteenMinutes()->withoutOverlapping();

// Shared hosting (cPanel) has no long-running queue worker. Run one cron entry,
//   * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
// and this drains queued emails and notifications every minute.
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();
