<?php

namespace OwaisKit\AuthTracker\Facades;

use OwaisKit\AuthTracker\Testing\TrackerFake;
use OwaisKit\AuthTracker\TrackerManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \OwaisKit\AuthTracker\Contracts\TrackerDriver driver(string|null $driver = null)
 * @method static \OwaisKit\AuthTracker\Contracts\TrackerDriver driverFor(string|null $guard)
 * @method static string driverNameFor(string|null $guard)
 * @method static string|null guardForDriver(string $driver)
 * @method static TrackerManager extend(string $driver, \Closure $callback)
 * @method static TrackerManager resolveTenantUsing(\Closure|null $callback)
 * @method static string|null resolveTenant()
 * @method static TrackerManager resolveDeviceUsing(\Closure|null $callback)
 * @method static \OwaisKit\AuthTracker\Support\DeviceSignal deviceSignal(\Illuminate\Http\Request $request)
 * @method static string deviceModel()
 * @method static string loginModel()
 * @method static bool isTracked(mixed $user)
 * @method static array trackable(object|string $model)
 * @method static \OwaisKit\AuthTracker\SessionManager sessions()
 * @method static \Illuminate\Database\Eloquent\Collection active(\Illuminate\Contracts\Auth\Authenticatable $user, string|null $guard = null)
 * @method static \Illuminate\Database\Eloquent\Collection history(\Illuminate\Contracts\Auth\Authenticatable $user, int $days = 90)
 * @method static \OwaisKit\AuthTracker\Models\Login|null current(\Illuminate\Contracts\Auth\Authenticatable|null $user = null)
 * @method static \OwaisKit\AuthTracker\Models\Login|null find(\Illuminate\Contracts\Auth\Authenticatable $user, int|string $id)
 * @method static bool revoke(\OwaisKit\AuthTracker\Models\Login $login, string $reason = 'user')
 * @method static int revokeOthers(\Illuminate\Contracts\Auth\Authenticatable $user, string $reason = 'user')
 * @method static int revokeAll(\Illuminate\Contracts\Auth\Authenticatable $user, string $reason = 'user')
 * @method static int revokeDevice(\OwaisKit\AuthTracker\Models\Device $device, string $reason = 'user')
 * @method static \Illuminate\Database\Eloquent\Collection devices(\Illuminate\Contracts\Auth\Authenticatable $user)
 * @method static void renameDevice(\OwaisKit\AuthTracker\Models\Device $device, string $name)
 * @method static int forgetDevice(\OwaisKit\AuthTracker\Models\Device $device)
 * @method static \OwaisKit\AuthTracker\Models\Device|null currentDevice()
 * @method static void trustDevice(\OwaisKit\AuthTracker\Models\Device $device, \Carbon\CarbonInterval|null $for = null)
 * @method static void untrustDevice(\OwaisKit\AuthTracker\Models\Device $device)
 * @method static bool isTrustedDevice(\OwaisKit\AuthTracker\Models\Device|null $device = null, \Illuminate\Contracts\Auth\Authenticatable|null $user = null)
 * @method static int blockDevice(\OwaisKit\AuthTracker\Models\Device $device, string $reason = 'security')
 * @method static void unblockDevice(\OwaisKit\AuthTracker\Models\Device $device)
 * @method static void forgetCurrent()
 * @method static void routes(string $prefix = 'auth-tracker', array|string $middleware = ['auth'], array|null $only = null, array|null $except = null, string $name = 'auth-tracker.')
 * @method static \OwaisKit\AuthTracker\Support\IssuedToken issueToken(\Illuminate\Contracts\Auth\Authenticatable $user, string $name = 'api', array $abilities = ['*'], string|null $guard = null)
 * @method static \OwaisKit\AuthTracker\Support\IssuedToken refreshToken(string $refreshToken)
 * @method static array driverNames()
 *
 * @see \OwaisKit\AuthTracker\TrackerManager
 * @see \OwaisKit\AuthTracker\SessionManager
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
