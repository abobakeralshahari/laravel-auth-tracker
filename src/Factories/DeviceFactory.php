<?php

namespace Alshahari\AuthTracker\Factories;

use Alshahari\AuthTracker\Middleware\StoreDevice;
use Alshahari\AuthTracker\Models\Device;
use Alshahari\AuthTracker\Services\DeviceService;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;

class DeviceFactory
{
    /**
     * Resolve the device of the given request, creating it when unknown.
     *
     * The device is resolved once per request and cached in the request
     * attributes, so middleware and listeners share the same instance.
     *
     * @throws UnauthorizedException
     */
    public static function build(bool $isRequired = false, ?Request $request = null): Device
    {
        $request = $request ?? request();

        if ($cached = $request->attributes->get(StoreDevice::REQUEST_KEY)) {
            return $cached;
        }

        $udid = $request->header(config('auth_tracker.device.header_prefix', 'x-device-').'udid');

        if (empty($udid) && $isRequired) {
            throw new UnauthorizedException('You need to specify your device details.');
        }

        $service = new DeviceService($request);
        $service->setHeader();

        $device = $service->saveDevice();

        $request->attributes->set(StoreDevice::REQUEST_KEY, $device);

        return $device;
    }
}
