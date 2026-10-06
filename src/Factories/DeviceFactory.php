<?php

namespace OwaisKit\AuthTracker\Factories;

use OwaisKit\AuthTracker\Actions\ResolveDevice;
use OwaisKit\AuthTracker\Models\Device;
use OwaisKit\AuthTracker\Support\DeviceSignal;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;

/**
 * @deprecated Use the ResolveDevice action.
 */
class DeviceFactory
{
    /**
     * Resolve the device of the given request, creating it when unknown.
     *
     * @throws UnauthorizedException
     */
    public static function build(bool $isRequired = false, ?Request $request = null): Device
    {
        $request = $request ?? request();

        if ($isRequired && ! $request->hasHeader(DeviceSignal::headerName('udid'))) {
            throw new UnauthorizedException('You need to specify your device details.');
        }

        return app(ResolveDevice::class)->execute($request);
    }
}
