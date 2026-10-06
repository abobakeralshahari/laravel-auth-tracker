<?php

namespace Awsan\AuthTracker;

use Awsan\AuthTracker\Factories\IpProviderFactory;
use Awsan\AuthTracker\Listeners\AuthEventSubscriber;
use Awsan\AuthTracker\Listeners\PassportEventSubscriber;
use Awsan\AuthTracker\Listeners\SanctumEventSubscriber;
use Awsan\AuthTracker\Macros\RouteMacros;
use Awsan\AuthTracker\Middleware\EnsureDeviceNotBlocked;
use Awsan\AuthTracker\Middleware\StoreDevice;
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

        $this->app->singleton(TrackerManager::class, fn ($app) => new TrackerManager($app));
        $this->app->singleton(SessionManager::class);
        $this->app->alias(TrackerManager::class, 'auth-tracker');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\InstallCommand::class,
                Commands\PruneCommand::class,
                Commands\DoctorCommand::class,
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
        $this->app['router']->aliasMiddleware('device.not-blocked', EnsureDeviceNotBlocked::class);

        Route::mixin(new RouteMacros);
    }

    protected function registerBlade(): void
    {
        Blade::if('tracked', function () {
            return $this->app->make(TrackerManager::class)->isTracked(request()->user());
        });

        Blade::if('ipLookup', function () {
            return IpProviderFactory::ipLookupEnabled();
        });
    }
}
