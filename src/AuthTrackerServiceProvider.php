<?php

namespace Alshahari\AuthTracker;

use Alshahari\AuthTracker\Factories\IpProviderFactory;
use Alshahari\AuthTracker\Macros\RouteMacros;
use Alshahari\AuthTracker\Middleware\StoreDevice;
use Alshahari\AuthTracker\Services\AuthTrackerService;
use Alshahari\AuthTracker\Services\DeviceService;
use Alshahari\AuthTracker\Services\NotificationService;
use Alshahari\AuthTracker\Services\SecurityService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AuthTrackerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Merge default config
        $this->mergeConfigFrom(
            __DIR__.'/../config/auth_tracker.php', 'auth_tracker'
        );

        // Register services
        $this->app->singleton(DeviceService::class, function ($app) {
            return new DeviceService();
        });

        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService();
        });

        $this->app->singleton(SecurityService::class, function ($app) {
            return new SecurityService();
        });

        $this->app->singleton(AuthTrackerService::class, function ($app) {
            return new AuthTrackerService(
                $app->make(DeviceService::class),
                $app->make(NotificationService::class),
                $app->make(SecurityService::class)
            );
        });

        // Register commands
        $this->commands([
            Commands\InstallCommand::class,
        ]);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/auth_tracker.php' => config_path('auth_tracker.php'),
        ], 'config');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Register extended Eloquent user provider
        Auth::provider('eloquent-tracked', function ($app, array $config) {
            return new EloquentUserProviderExtended($app['hash'], $config['model']);
        });

        // Register event subscribers
        Event::subscribe('Alshahari\AuthTracker\Listeners\PassportEventSubscriber');
        Event::subscribe('Alshahari\AuthTracker\Listeners\AuthEventSubscriber');
        Event::subscribe('Alshahari\AuthTracker\Listeners\SanctumEventSubscriber');

        // Register middleware
        $router = $this->app['router'];
        $router->aliasMiddleware('store.device', StoreDevice::class);
        
        // Register route macros
        Route::mixin(new RouteMacros);

        // Register Blade directives
        Blade::if('tracked', function () {
            return method_exists(request()->user(), 'logins');
        });
        Blade::if('ipLookup', function () {
            return IpProviderFactory::ipLookupEnabled();
        });

        // Auto-register service if enabled
        if (config('auth_tracker.service.auto_track_logins', true)) {
            $this->registerAutoTracking();
        }
    }

    /**
     * Register auto-tracking functionality
     *
     * @return void
     */
    protected function registerAutoTracking()
    {
        // Listen for login events and automatically track them
        Event::listen('Illuminate\Auth\Events\Login', function ($event) {
            if (method_exists($event->user, 'logins')) {
                app(AuthTrackerService::class)->trackLogin($event->user, [
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'login_by' => 'web',
                    'login_from' => $this->detectLoginFrom(),
                ], $event->remember ?? false);
            }
        });
    }

    /**
     * Detect login source
     *
     * @return string
     */
    protected function detectLoginFrom(): string
    {
        $agent = new \Jenssegers\Agent\Agent();
        
        if ($agent->isDesktop()) {
            return 'web_pc';
        }
        
        if ($agent->isMobile()) {
            return 'web_mobile';
        }
        
        if ($agent->isTablet()) {
            return 'web_tablet';
        }
        
        return 'other';
    }
}
