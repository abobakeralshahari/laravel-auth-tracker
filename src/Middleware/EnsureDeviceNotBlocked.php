<?php

namespace Awsan\AuthTracker\Middleware;

use Awsan\AuthTracker\Actions\ResolveDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Reject requests coming from a blocked device.
 *
 *   Route::middleware('device.not-blocked')
 */
class EnsureDeviceNotBlocked
{
    public function handle(Request $request, Closure $next)
    {
        $device = app(ResolveDevice::class)->execute($request);

        if ($device->isBlocked()) {
            throw new HttpException(403, 'This device is blocked.');
        }

        return $next($request);
    }
}
