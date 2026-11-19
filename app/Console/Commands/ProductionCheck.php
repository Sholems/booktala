<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Checks the settings that are safe in development but dangerous on a live
 * server. Run it after every deploy; it fails (exit code 1) on any problem.
 */
class ProductionCheck extends Command
{
    protected $signature = 'app:production-check';

    protected $description = 'Check that configuration is safe for a live server';

    public function handle(): int
    {
        $problems = [];
        $warnings = [];

        if (! config('app.key')) {
            $problems[] = 'APP_KEY is not set. Run: php artisan key:generate';
        }
        if (config('app.debug')) {
            $problems[] = 'APP_DEBUG is on: error pages would show code, queries and secrets. Set APP_DEBUG=false.';
        }
        if (! app()->environment('production')) {
            $problems[] = 'APP_ENV is "'.app()->environment().'". Set APP_ENV=production.';
        }
        if (config('telescope.enabled')) {
            $problems[] = 'Telescope is recording requests. Set TELESCOPE_ENABLED=false.';
        }
        if (config('database.default') === 'sqlite') {
            $problems[] = 'The database is SQLite, which ignores the row locks used for bookings, payments and stock. Use MySQL/MariaDB.';
        }
        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            $warnings[] = 'Mail is going to the '.config('mail.default').' driver, so guests and staff receive no email.';
        }
        if (config('queue.default') === 'sync') {
            $warnings[] = 'QUEUE_CONNECTION=sync sends notifications during requests. Use database with the scheduler cron.';
        }
        if (! str_starts_with((string) config('app.url'), 'https://')) {
            $warnings[] = 'APP_URL is not https: payment callbacks and links should use https.';
        }
        if (! config('session.secure')) {
            $warnings[] = 'SESSION_SECURE_COOKIE is off: login cookies can travel over plain http.';
        }

        foreach ($problems as $problem) {
            $this->error('✗ '.$problem);
        }
        foreach ($warnings as $warning) {
            $this->warn('! '.$warning);
        }

        if ($problems) {
            $this->line('');
            $this->error(count($problems).' problem(s) must be fixed before going live.');

            return self::FAILURE;
        }

        $this->info('Configuration is safe for production'.($warnings ? ' (see warnings above).' : '.'));

        return self::SUCCESS;
    }
}
