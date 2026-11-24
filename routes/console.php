<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('bookings:send-checkin-reminders')->dailyAt('8am')->onOneServer();

// Escalate unstarted work, report missed shifts, close forgotten clock-ins.
Schedule::command('operations:check')->everyFifteenMinutes()->withoutOverlapping()->onOneServer();

Schedule::command('bookings:expire-holds')->everyMinute()->withoutOverlapping()->onOneServer();

// Two-way synchronization with Airbnb, Booking.com, VRBO calendars
Schedule::command('ota:sync')->everyFifteenMinutes()->withoutOverlapping()->onOneServer();

// Mature affiliate commissions 24 hours post-stay
Schedule::command('affiliates:mature-commissions')->hourly()->withoutOverlapping()->onOneServer();
