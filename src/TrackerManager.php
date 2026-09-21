<?php

namespace Alshahari\AuthTracker;

use Alshahari\AuthTracker\Contracts\TrackerDriver;
use Alshahari\AuthTracker\Drivers\PassportDriver;
use Alshahari\AuthTracker\Drivers\SanctumDriver;
use Alshahari\AuthTracker\Drivers\SessionDriver;
use Alshahari\AuthTracker\Support\DeviceSignal;
use Alshahari\AuthTracker\Support\IssuedToken;
use Alshahari\AuthTracker\Traits\AuthTracking;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Manager;
use InvalidArgumentException;

/**
 * Entry point of the package: resolves tracker drivers per guard, holds the
 * runtime customizations and forwards the session operations to the
 * SessionManager.
 *
 * @mixin \Alshahari\AuthTracker\SessionManager
 */
class TrackerManager extends Manager
{
    protected ?Closure $tenantResolver = null;

    protected ?Closure $deviceSignalResolver = null;

    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return 'session';
    }

    /**
     * Get the driver used by a guard.
     *
     * Configured in auth_tracker.guards, or inferred from the guard's
     * auth driver (session, sanctum, passport).
     */
    public function driverFor(?string $guard): TrackerDriver
    {
        return $this->driver($this->driverNameFor($guard));
    }

    public function driverNameFor(?string $guard): string
    {
        $guard = $guard ?: config('auth.defaults.guard');

        if ($driver = config("auth_tracker.guards.{$guard}.driver")) {
            return $driver;
        }

        $authDriver = config("auth.guards.{$guard}.driver", 'session');

        // Custom auth drivers may share the name of a tracker driver.
        return in_array($authDriver, ['session', 'sanctum', 'passport'], true) || isset($this->customCreators[$authDriver])
            ? $authDriver
            : 'session';
    }

    /**
     * Name of the (first) guard using the given tracker driver.
     */
    public function guardForDriver(string $driver): ?string
    {
        foreach ((array) config('auth_tracker.guards', []) as $guard => $config) {
            if (($config['driver'] ?? null) === $driver) {
                return $guard;
            }
        }

        foreach ((array) config('auth.guards', []) as $guard => $config) {
            if (($config['driver'] ?? null) === $driver) {
                return $guard;
            }
        }

        return null;
    }

    /**
     * Names of all the available drivers, API token drivers first so that
     * a stateful API request resolves to its token rather than its session.
     *
     * @return list<string>
     */
    public function driverNames(): array
    {
        $names = array_keys($this->customCreators);

        if (class_exists(\Laravel\Passport\Passport::class)) {
            $names[] = 'passport';
        }

        if (class_exists(\Laravel\Sanctum\Sanctum::class)) {
            $names[] = 'sanctum';
        }

        $names[] = 'session';

        return array_values(array_unique($names));
    }

    protected function createSessionDriver(): TrackerDriver
    {
        return new SessionDriver;
    }

    protected function createSanctumDriver(): TrackerDriver
    {
        return new SanctumDriver;
    }

    protected function createPassportDriver(): TrackerDriver
    {
        return new PassportDriver;
    }

    /**
     * Create a new driver instance.
     *
     * @throws InvalidArgumentException
     */
    protected function createDriver($driver)
    {
        $instance = parent::createDriver($driver);

        if (! $instance instanceof TrackerDriver) {
            throw new InvalidArgumentException("Tracker driver [{$driver}] must implement ".TrackerDriver::class.'.');
        }

        return $instance;
    }

    // ------------------------------------------------------------------
    //  Customizations
    // ------------------------------------------------------------------

    /**
     * Customize how the tenant identifier of a device is resolved.
     *
     * AuthTracker::resolveTenantUsing(fn () => tenant()?->getTenantKey());
     */
    public function resolveTenantUsing(?Closure $callback): static
    {
        $this->tenantResolver = $callback;

        return $this;
    }

    public function resolveTenant(): ?string
    {
        if ($this->tenantResolver) {
            $tenant = call_user_func($this->tenantResolver);

            return $tenant === null ? null : (string) $tenant;
        }

        // Backward compatibility with projects using stancl/tenancy.
        if (function_exists('tenant') && ($tenant = tenant())) {
            return (string) $tenant->getTenantKey();
        }

        return null;
    }

    /**
     * Customize how the device is read from the request.
     *
     * AuthTracker::resolveDeviceUsing(fn (Request $r) => DeviceSignal::fromRequest($r)->withUdid(...));
     */
    public function resolveDeviceUsing(?Closure $callback): static
    {
        $this->deviceSignalResolver = $callback;

        return $this;
    }

    public function deviceSignal(Request $request): DeviceSignal
    {
        if ($this->deviceSignalResolver) {
            return call_user_func($this->deviceSignalResolver, $request);
        }

        return DeviceSignal::fromRequest($request, $this->resolveTenant());
    }

    // ------------------------------------------------------------------
    //  Models & trackables
    // ------------------------------------------------------------------

    /**
     * @return class-string<\Alshahari\AuthTracker\Models\Device>
     */
    public function deviceModel(): string
    {
        return config('auth_tracker.device_model', Models\Device::class);
    }

    /**
     * @return class-string<\Alshahari\AuthTracker\Models\Login>
     */
    public function loginModel(): string
    {
        return config('auth_tracker.models.login', Models\Login::class);
    }

    /**
     * Determine if the given user is tracked (uses the AuthTracking trait).
     */
    public function isTracked(mixed $user): bool
    {
        return is_object($user) && in_array(AuthTracking::class, class_uses_recursive($user), true);
    }

    /**
     * Per-model configuration (auth_tracker.trackables).
     */
    public function trackable(object|string $model): array
    {
        $class = is_object($model) ? get_class($model) : $model;

        foreach ((array) config('auth_tracker.trackables', []) as $trackable => $config) {
            if (is_a($class, $trackable, true)) {
                return (array) $config;
            }
        }

        return [];
    }

    public function sessions(): SessionManager
    {
        return $this->container->make(SessionManager::class);
    }

    /**
     * Register the "my sessions / my devices" API routes.
     *
     * AuthTracker::routes(prefix: 'account/security', middleware: ['auth:sanctum']);
     *
     * @param  list<string>|null  $only
     * @param  list<string>|null  $except
     */
    public function routes(
        string $prefix = 'auth-tracker',
        array|string $middleware = ['auth'],
        ?array $only = null,
        ?array $except = null,
        string $name = 'auth-tracker.',
    ): void {
        $this->container->make(Http\RouteRegistrar::class)->register($prefix, $middleware, $only, $except, $name);
    }

    // ------------------------------------------------------------------
    //  Refreshable Sanctum tokens
    // ------------------------------------------------------------------

    /**
     * Issue a short lived access token with a rotating refresh token.
     *
     * @param  list<string>  $abilities
     */
    public function issueToken(Authenticatable $user, string $name = 'api', array $abilities = ['*'], ?string $guard = null): IssuedToken
    {
        return $this->container->make(TokenIssuer::class)->issue($user, $name, $abilities, $guard);
    }

    /**
     * @throws \Alshahari\AuthTracker\Exceptions\InvalidRefreshTokenException
     */
    public function refreshToken(string $refreshToken): IssuedToken
    {
        return $this->container->make(TokenIssuer::class)->refresh($refreshToken);
    }

    /**
     * Forward the session operations to the SessionManager.
     */
    public function __call($method, $parameters)
    {
        if (method_exists(SessionManager::class, $method)) {
            return $this->sessions()->{$method}(...$parameters);
        }

        return parent::__call($method, $parameters);
    }
}
