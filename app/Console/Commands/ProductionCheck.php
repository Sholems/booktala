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
        if (config('database.default') !== 'pgsql') {
            $problems[] = 'The production database must be PostgreSQL. Set DB_CONNECTION=pgsql.';
        }
        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            $problems[] = 'Mail is going to the '.config('mail.default').' driver, so guests and staff receive no email.';
        }
        if (config('queue.default') === 'sync') {
            $problems[] = 'QUEUE_CONNECTION=sync sends notifications during requests. Use database or redis with a dedicated worker.';
        }
        if (! in_array(config('cache.default'), ['database', 'redis'], true)) {
            $problems[] = 'CACHE_STORE must be database or redis so rate limits and scheduler locks survive across processes.';
        }
        if (! in_array(config('session.driver'), ['database', 'redis'], true)) {
            $problems[] = 'SESSION_DRIVER must be database or redis in production.';
        }
        if (! str_starts_with((string) config('app.url'), 'https://')) {
            $problems[] = 'APP_URL is not https: payment callbacks and links must use https.';
        }
        if (! config('session.secure')) {
            $problems[] = 'SESSION_SECURE_COOKIE is off: login cookies can travel over plain http.';
        }
        if (! config('session.encrypt')) {
            $problems[] = 'SESSION_ENCRYPT is off. Enable encrypted session payloads in production.';
        }
        if (! config('trustedproxy.proxies')) {
            $problems[] = 'TRUSTED_PROXIES is not configured. Set it to * for an app reachable only through the Coolify proxy.';
        }
        if (! config('services.paystack.public') || ! config('services.paystack.secret')) {
            $problems[] = 'Paystack credentials are missing. Set PAYSTACK_PUBLIC_KEY and PAYSTACK_SECRET_KEY.';
        }

        if (config('filesystems.default') === 'r2') {
            $publicBucket = config('filesystems.disks.public.bucket');
            $privateBucket = config('filesystems.disks.r2.bucket');

            if (! $publicBucket || ! $privateBucket) {
                $problems[] = 'R2 requires CLOUDFLARE_R2_PUBLIC_BUCKET and CLOUDFLARE_R2_PRIVATE_BUCKET.';
            } elseif ($publicBucket === $privateBucket) {
                $problems[] = 'R2 public and private uploads must use separate buckets.';
            }
            if (! config('filesystems.disks.r2.endpoint')) {
                $problems[] = 'R2 endpoint is missing. Set CLOUDFLARE_R2_ENDPOINT.';
            }
            if (! config('filesystems.disks.public.url')) {
                $problems[] = 'R2 public media URL is missing. Set CLOUDFLARE_R2_PUBLIC_URL.';
            }
        }

        foreach ($problems as $problem) {
            $this->error('✗ '.$problem);
        }
        if ($problems) {
            $this->line('');
            $this->error(count($problems).' problem(s) must be fixed before going live.');

            return self::FAILURE;
        }

        $this->info('Configuration is safe for production.');

        return self::SUCCESS;
    }
}
