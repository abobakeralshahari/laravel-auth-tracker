<?php

namespace Awsan\AuthTracker\Facades;

use Awsan\AuthTracker\Testing\TrackerFake;
use Awsan\AuthTracker\TrackerManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Awsan\AuthTracker\Contracts\TrackerDriver driver(string|null $driver = null)
 * @method static \Awsan\AuthTracker\Contracts\TrackerDriver driverFor(string|null $guard)
 * @method static string driverNameFor(string|null $guard)
 * @method static string|null guardForDriver(string $driver)
 * @method static TrackerManager extend(string $driver, \Closure $callback)
 * @method static TrackerManager resolveTenantUsing(\Closure|null $callback)
 * @method static string|null resolveTenant()
 * @method static TrackerManager resolveDeviceUsing(\Closure|null $callback)
 * @method static \Awsan\AuthTracker\Support\DeviceSignal deviceSignal(\Illuminate\Http\Request $request)
 * @method static string deviceModel()
 * @method static string loginModel()
 * @method static bool isTracked(mixed $user)
 * @method static array trackable(object|string $model)
 * @method static \Awsan\AuthTracker\SessionManager sessions()
 * @method static \Illuminate\Database\Eloquent\Collection active(\Illuminate\Contracts\Auth\Authenticatable $user, string|null $guard = null)
 * @method static \Illuminate\Database\Eloquent\Collection history(\Illuminate\Contracts\Auth\Authenticatable $user, int $days = 90)
 * @method static \Awsan\AuthTracker\Models\Login|null current(\Illuminate\Contracts\Auth\Authenticatable|null $user = null)
 * @method static \Awsan\AuthTracker\Models\Login|null find(\Illuminate\Contracts\Auth\Authenticatable $user, int|string $id)
 * @method static bool revoke(\Awsan\AuthTracker\Models\Login $login, string $reason = 'user')
 * @method static int revokeOthers(\Illuminate\Contracts\Auth\Authenticatable $user, string $reason = 'user')
 * @method static int revokeAll(\Illuminate\Contracts\Auth\Authenticatable $user, string $reason = 'user')
 * @method static int revokeDevice(\Awsan\AuthTracker\Models\Device $device, string $reason = 'user')
 * @method static \Illuminate\Database\Eloquent\Collection devices(\Illuminate\Contracts\Auth\Authenticatable $user)
 * @method static void renameDevice(\Awsan\AuthTracker\Models\Device $device, string $name)
 * @method static int forgetDevice(\Awsan\AuthTracker\Models\Device $device)
 * @method static \Awsan\AuthTracker\Models\Device|null currentDevice()
 * @method static void trustDevice(\Awsan\AuthTracker\Models\Device $device, \Carbon\CarbonInterval|null $for = null)
 * @method static void untrustDevice(\Awsan\AuthTracker\Models\Device $device)
 * @method static bool isTrustedDevice(\Awsan\AuthTracker\Models\Device|null $device = null, \Illuminate\Contracts\Auth\Authenticatable|null $user = null)
 * @method static int blockDevice(\Awsan\AuthTracker\Models\Device $device, string $reason = 'security')
 * @method static void unblockDevice(\Awsan\AuthTracker\Models\Device $device)
 * @method static void forgetCurrent()
 * @method static void routes(string $prefix = 'auth-tracker', array|string $middleware = ['auth'], array|null $only = null, array|null $except = null, string $name = 'auth-tracker.')
 * @method static \Awsan\AuthTracker\Support\IssuedToken issueToken(\Illuminate\Contracts\Auth\Authenticatable $user, string $name = 'api', array $abilities = ['*'], string|null $guard = null)
 * @method static \Awsan\AuthTracker\Support\IssuedToken refreshToken(string $refreshToken)
 * @method static array driverNames()
 *
 * @see \Awsan\AuthTracker\TrackerManager
 * @see \Awsan\AuthTracker\SessionManager
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
