<?php

namespace OwaisKit\AuthTracker\Middleware;

use OwaisKit\AuthTracker\Actions\ResolveDevice;
use OwaisKit\AuthTracker\Support\DeviceSignal;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;

/**
 * Resolve (and persist) the client device for the current request.
 *
 * Usage: Route::middleware('store.device')            optional device headers
 *        Route::middleware('store.device:api,true')   device headers required
 */
class StoreDevice
{
    /**
     * Request attribute holding the resolved device.
     */
    const REQUEST_KEY = ResolveDevice::REQUEST_KEY;

    /**
     * Request attribute holding the guard name.
     */
    const GUARD_KEY = 'auth_tracker.guard';

    /**
     * @param  string|null  $guard
     * @param  bool|string  $isRequired
     */
    public function handle(Request $request, Closure $next, $guard = null, $isRequired = false)
    {
        $isRequired = filter_var($isRequired, FILTER_VALIDATE_BOOLEAN);

        if ($isRequired && ! $request->hasHeader(DeviceSignal::headerName('udid'))) {
            throw new UnauthorizedException('You need to specify your device details.');
        }

        $device = app(ResolveDevice::class)->execute($request);

        $request->attributes->set(self::REQUEST_KEY, $device);
        $request->attributes->set(self::GUARD_KEY, $guard ?: config('auth.defaults.guard'));

        return $next($request);
    }

    /**
     * Attach the device to the authenticated user once the response is sent.
     */
    public function terminate(Request $request, $response): void
    {
        $device = $request->attributes->get(self::REQUEST_KEY);
        $user = $request->user($request->attributes->get(self::GUARD_KEY));

        if ($device && $user && ! $device->deviceable()->is($user)) {
            $device->deviceable()->associate($user);
            $device->save();
        }
    }
}
