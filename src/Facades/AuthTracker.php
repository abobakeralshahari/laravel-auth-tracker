<?php

namespace Alshahari\AuthTracker\Facades;

use Alshahari\AuthTracker\Testing\TrackerFake;
use Alshahari\AuthTracker\TrackerManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Alshahari\AuthTracker\Contracts\TrackerDriver driver(string|null $driver = null)
 * @method static \Alshahari\AuthTracker\Contracts\TrackerDriver driverFor(string|null $guard)
 * @method static string driverNameFor(string|null $guard)
 * @method static string|null guardForDriver(string $driver)
 * @method static TrackerManager extend(string $driver, \Closure $callback)
 * @method static TrackerManager resolveTenantUsing(\Closure|null $callback)
 * @method static string|null resolveTenant()
 * @method static TrackerManager resolveDeviceUsing(\Closure|null $callback)
 * @method static \Alshahari\AuthTracker\Support\DeviceSignal deviceSignal(\Illuminate\Http\Request $request)
 * @method static string deviceModel()
 * @method static string loginModel()
 * @method static bool isTracked(mixed $user)
 * @method static array trackable(object|string $model)
 * @method static \Alshahari\AuthTracker\SessionManager sessions()
 * @method static \Illuminate\Database\Eloquent\Collection active(\Illuminate\Contracts\Auth\Authenticatable $user, string|null $guard = null)
 * @method static \Illuminate\Database\Eloquent\Collection history(\Illuminate\Contracts\Auth\Authenticatable $user, int $days = 90)
 * @method static \Alshahari\AuthTracker\Models\Login|null current(\Illuminate\Contracts\Auth\Authenticatable|null $user = null)
 * @method static \Alshahari\AuthTracker\Models\Login|null find(\Illuminate\Contracts\Auth\Authenticatable $user, int|string $id)
 * @method static bool revoke(\Alshahari\AuthTracker\Models\Login $login, string $reason = 'user')
 * @method static int revokeOthers(\Illuminate\Contracts\Auth\Authenticatable $user, string $reason = 'user')
 * @method static int revokeAll(\Illuminate\Contracts\Auth\Authenticatable $user, string $reason = 'user')
 * @method static int revokeDevice(\Alshahari\AuthTracker\Models\Device $device, string $reason = 'user')
 * @method static \Illuminate\Database\Eloquent\Collection devices(\Illuminate\Contracts\Auth\Authenticatable $user)
 * @method static void renameDevice(\Alshahari\AuthTracker\Models\Device $device, string $name)
 * @method static int forgetDevice(\Alshahari\AuthTracker\Models\Device $device)
 * @method static \Alshahari\AuthTracker\Models\Device|null currentDevice()
 * @method static void trustDevice(\Alshahari\AuthTracker\Models\Device $device, \Carbon\CarbonInterval|null $for = null)
 * @method static void untrustDevice(\Alshahari\AuthTracker\Models\Device $device)
 * @method static bool isTrustedDevice(\Alshahari\AuthTracker\Models\Device|null $device = null, \Illuminate\Contracts\Auth\Authenticatable|null $user = null)
 * @method static int blockDevice(\Alshahari\AuthTracker\Models\Device $device, string $reason = 'security')
 * @method static void unblockDevice(\Alshahari\AuthTracker\Models\Device $device)
 * @method static void forgetCurrent()
 * @method static void routes(string $prefix = 'auth-tracker', array|string $middleware = ['auth'], array|null $only = null, array|null $except = null, string $name = 'auth-tracker.')
 * @method static \Alshahari\AuthTracker\Support\IssuedToken issueToken(\Illuminate\Contracts\Auth\Authenticatable $user, string $name = 'api', array $abilities = ['*'], string|null $guard = null)
 * @method static \Alshahari\AuthTracker\Support\IssuedToken refreshToken(string $refreshToken)
 * @method static array driverNames()
 *
 * @see \Alshahari\AuthTracker\TrackerManager
 * @see \Alshahari\AuthTracker\SessionManager
 */
class AuthTracker extends Facade
{
    /**
     * Record the package events for assertions (tracking stays active).
     */
    public static function fake(): TrackerFake
    {
        return TrackerFake::install(static::$app);
    }

    protected static function getFacadeAccessor(): string
    {
        return TrackerManager::class;
    }
}
