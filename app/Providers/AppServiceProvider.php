<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\SettingsService::class, function () {
            return new \App\Services\SettingsService();
        });
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Super admins may do everything; everyone else is checked against their role's permissions.
        \Illuminate\Support\Facades\Gate::before(
            fn ($user) => method_exists($user, 'hasRole') && $user->hasRole('super_admin') ? true : null
        );

        // Listeners in app/Listeners are auto-discovered; registering them here
        // as well would run each one twice.
        $this->optimizeDashboardQueries();
        $this->shareGlobalSettings();
    }

    private function optimizeDashboardQueries(): void
    {
        if (!request()->is('admin/dashboard')) {
            return;
        }

        $cacheKey = 'admin_dashboard_stats_' . date('Y-m-d-H');
        
        View::share('dashboardCache', $cacheKey);
    }

    private function shareGlobalSettings(): void
    {
        // Always provide a fallback to keep views resilient.
        View::share('systemSettings', []);

        // Avoid DB-dependent view boot logic while running artisan commands
        // (especially first-time migrations on new environments like cPanel).
        if ($this->app->runningInConsole()) {
            return;
        }

        try {
            if (!Schema::hasTable('system_settings')) {
                return;
            }

            $settings = Cache::remember('system_settings', 3600, static function () {
                return DB::table('system_settings')->pluck('value', 'key')->toArray();
            });

            View::share('systemSettings', $settings);
        } catch (\Throwable $e) {
            // Safe fallback when DB is not ready yet.
            View::share('systemSettings', []);
        }
    }
}
