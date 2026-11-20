<?php

namespace App\Providers;

use App\Services\SettingsService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsService::class, function () {
            return new SettingsService;
        });
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        if ($this->app->environment('production') || str_starts_with((string) config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Super admins may do everything; everyone else is checked against their role's permissions.
        Gate::before(
            fn ($user) => method_exists($user, 'hasRole') && $user->hasRole('super_admin') ? true : null
        );

        // Listeners in app/Listeners are auto-discovered; registering them here
        // as well would run each one twice.
        $this->configureRateLimiters();
        $this->shareGlobalSettings();
        $this->reportLazyLoading();
    }

    /**
     * Outside production, log every lazy-loaded relation (a likely N+1 query)
     * with the model and relation, once per request, without breaking the page.
     */
    private function reportLazyLoading(): void
    {
        if ($this->app->isProduction()) {
            return;
        }

        Model::preventLazyLoading();
        Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation) {
            static $seen = [];
            $key = $model::class.'::'.$relation;
            if (isset($seen[$key])) {
                return;
            }
            $seen[$key] = true;

            Log::channel(config('logging.lazy_loading_channel', config('logging.default')))
                ->warning('Lazy loading '.$key.' (possible N+1 query: eager load it with ->with()).', [
                    'url' => request()?->fullUrl(),
                ]);
        });
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('auth-api', fn (Request $request) => Limit::perMinute(5)->by(
            strtolower((string) $request->input('email')).'|'.$request->ip(),
        ));

        RateLimiter::for('booking', fn (Request $request) => Limit::perMinute(5)->by(
            $request->user()?->id ?: $request->ip(),
        ));

        RateLimiter::for('payment', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
        RateLimiter::for('contact', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('availability', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
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
            if (! Schema::hasTable('system_settings')) {
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
