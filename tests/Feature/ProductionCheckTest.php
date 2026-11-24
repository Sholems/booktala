<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductionCheckTest extends TestCase
{
    public function test_it_fails_on_development_settings(): void
    {
        config(['app.debug' => true, 'database.default' => 'sqlite', 'telescope.enabled' => true]);

        $this->artisan('app:production-check')
            ->expectsOutputToContain('APP_DEBUG is on')
            ->expectsOutputToContain('Telescope is recording')
            ->expectsOutputToContain('PostgreSQL')
            ->assertFailed();
    }

    public function test_it_passes_a_production_configuration(): void
    {
        $this->app['env'] = 'production';
        config([
            'app.key' => 'base64:'.base64_encode(random_bytes(32)),
            'app.debug' => false,
            'app.url' => 'https://booktala.example',
            'telescope.enabled' => false,
            'database.default' => 'pgsql',
            'mail.default' => 'smtp',
            'queue.default' => 'database',
            'cache.default' => 'database',
            'session.driver' => 'database',
            'session.secure' => true,
            'session.encrypt' => true,
            'trustedproxy.proxies' => '*',
            'services.paystack.public' => 'pk_live_example',
            'services.paystack.secret' => 'sk_live_example',
        ]);

        $this->artisan('app:production-check')
            ->expectsOutputToContain('Configuration is safe for production')
            ->assertSuccessful();
    }

    public function test_it_rejects_missing_payment_credentials_and_non_postgresql_database(): void
    {
        $this->app['env'] = 'production';
        config([
            'app.key' => 'base64:'.base64_encode(random_bytes(32)),
            'app.debug' => false,
            'app.url' => 'https://booktala.example',
            'telescope.enabled' => false,
            'database.default' => 'mysql',
            'mail.default' => 'smtp',
            'queue.default' => 'database',
            'session.secure' => true,
            'session.encrypt' => true,
            'services.paystack.public' => null,
            'services.paystack.secret' => null,
        ]);

        $this->artisan('app:production-check')
            ->expectsOutputToContain('PostgreSQL')
            ->expectsOutputToContain('Paystack credentials')
            ->expectsOutputToContain('TRUSTED_PROXIES')
            ->assertFailed();
    }

    public function test_it_rejects_a_shared_public_and_private_r2_bucket(): void
    {
        $this->app['env'] = 'production';
        config([
            'app.key' => 'base64:'.base64_encode(random_bytes(32)),
            'app.debug' => false,
            'app.url' => 'https://booktala.example',
            'telescope.enabled' => false,
            'database.default' => 'pgsql',
            'mail.default' => 'smtp',
            'queue.default' => 'database',
            'session.secure' => true,
            'session.encrypt' => true,
            'services.paystack.public' => 'pk_live_example',
            'services.paystack.secret' => 'sk_live_example',
            'filesystems.default' => 'r2',
            'filesystems.disks.public.bucket' => 'shared-bucket',
            'filesystems.disks.public.url' => 'https://media.example.com',
            'filesystems.disks.r2.bucket' => 'shared-bucket',
        ]);

        $this->artisan('app:production-check')
            ->expectsOutputToContain('separate buckets')
            ->assertFailed();
    }
}
