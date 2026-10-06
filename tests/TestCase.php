<?php

namespace Awsan\AuthTracker\Tests;

use Awsan\AuthTracker\Facades\AuthTracker;
use Awsan\AuthTracker\AuthTrackerServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Laravel\Passport\PassportServiceProvider;
use Laravel\Sanctum\SanctumServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected static bool $passportKeysGenerated = false;

    protected function setUp(): void
    {
        parent::setUp();


        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../vendor/laravel/passport/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../vendor/laravel/sanctum/database/migrations');

        $this->artisan('migrate')->run();

        $this->setUpPassport();
        $this->setRoutes();
        $this->startSession();
    }

    /**
     * Give the current request a started session, as the StartSession
     * middleware would do in a real HTTP request.
     */
    protected function startSession(): void
    {
        $session = $this->app['session']->driver();
        $session->start();

        $this->app['request']->setLaravelSession($session);
    }

    protected function getPackageProviders($app): array
    {
        return [
            AuthTrackerServiceProvider::class,
            PassportServiceProvider::class,
            SanctumServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('auth.guards.api', [
            'driver' => 'passport',
            'provider' => 'passport_users',
        ]);

        $app['config']->set('auth.guards.sanctum', [
            'driver' => 'sanctum',
            'provider' => 'users',
        ]);

        $app['config']->set('auth.providers', [
            'users' => [
                'driver' => 'eloquent-tracked',
                'model' => User::class,
            ],
            'passport_users' => [
                'driver' => 'eloquent-tracked',
                'model' => PassportUser::class,
            ],
        ]);

        $app['config']->set('auth_tracker.parser', 'agent');
        $app['config']->set('session.driver', 'array');
    }

    protected function setUpPassport(): void
    {
        Passport::enablePasswordGrant();

        if (! static::$passportKeysGenerated) {
            $this->artisan('passport:keys', ['--force' => true])->run();
            static::$passportKeysGenerated = true;
        }
    }

    protected function setRoutes(): void
    {
        $routes = function () {
            Route::get('/check', fn (Request $request) => response()->json($request->user()->currentLogin()));
            Route::get('/active', fn (Request $request) => response()->json($request->user()->activeLogin()));
            Route::post('/logout/others', fn (Request $request) => response()->json($request->user()->logoutOthers()));
            Route::post('/logout/all', fn (Request $request) => response()->json($request->user()->logoutAll()));
            Route::post('/logout/{id?}', fn (Request $request, $id = null) => response()->json($request->user()->logout($id)));
        };

        Route::prefix('api')->middleware(['api', 'auth:api'])->group($routes);
        Route::prefix('sanctum')->middleware(['api', 'auth:sanctum'])->group($routes);

        Route::middleware(['web', 'store.device'])->get('/device', function (Request $request) {
            return response()->json($request->attributes->get('auth_tracker.device'));
        });
    }

    protected function createUser(string $class = User::class, array $attributes = [])
    {
        return $class::create($attributes + [
            'name' => 'Test User',
            'email' => Str::random(8).'@example.com',
            'password' => bcrypt('password'),
        ]);
    }
}
