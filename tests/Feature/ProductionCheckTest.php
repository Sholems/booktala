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
            ->expectsOutputToContain('SQLite')
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
            'database.default' => 'mysql',
            'mail.default' => 'smtp',
            'queue.default' => 'database',
            'session.secure' => true,
        ]);

        $this->artisan('app:production-check')
            ->expectsOutputToContain('Configuration is safe for production')
            ->assertSuccessful();
    }
}
