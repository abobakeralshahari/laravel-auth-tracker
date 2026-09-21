<?php

namespace Alshahari\AuthTracker;

use Alshahari\AuthTracker\Factories\IpProviderFactory;
use Alshahari\AuthTracker\Listeners\AuthEventSubscriber;
use Alshahari\AuthTracker\Listeners\PassportEventSubscriber;
use Alshahari\AuthTracker\Listeners\SanctumEventSubscriber;
use Alshahari\AuthTracker\Macros\RouteMacros;
use Alshahari\AuthTracker\Middleware\StoreDevice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AuthTrackerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/auth_tracker.php', 'auth_tracker');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\InstallCommand::class,
            ]);
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPublishables();
        $this->registerAuth();
        $this->registerRouting();
        $this->registerBlade();
    }

    protected function registerPublishables(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if (! $this->app->runningInConsole()) {
            return;
        }

        // "config" tag kept for backward compatibility.
        foreach (['auth-tracker-config', 'config'] as $tag) {
            $this->publishes([
                __DIR__.'/../config/auth_tracker.php' => config_path('auth_tracker.php'),
            ], $tag);
        }

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'auth-tracker-migrations');
    }

    protected function registerAuth(): void
    {
        Auth::provider('eloquent-tracked', function ($app, array $config) {
            return new EloquentUserProviderExtended($app['hash'], $config['model']);
        });

        Event::subscribe(AuthEventSubscriber::class);

        if (class_exists(\Laravel\Passport\Passport::class)) {
            Event::subscribe(PassportEventSubscriber::class);
        }

        if (class_exists(\Laravel\Sanctum\Sanctum::class)) {
            Event::subscribe(SanctumEventSubscriber::class);
        }
    }

    protected function registerRouting(): void
    {
        $this->app['router']->aliasMiddleware('store.device', StoreDevice::class);

        Route::mixin(new RouteMacros);
    }

    protected function registerBlade(): void
    {
        Blade::if('tracked', function () {
            return AuthTracker::isTracked(request()->user());
        });

        Blade::if('ipLookup', function () {
            return IpProviderFactory::ipLookupEnabled();
        });
    }
}
