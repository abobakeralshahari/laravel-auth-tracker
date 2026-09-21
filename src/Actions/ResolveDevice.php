<?php

namespace Alshahari\AuthTracker\Actions;

use Alshahari\AuthTracker\Events\DeviceRegistered;
use Alshahari\AuthTracker\Models\Device;
use Alshahari\AuthTracker\Support\DeviceSignal;
use Alshahari\AuthTracker\TrackerManager;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Find or create the device of a request and persist what it told us.
 *
 * The device is resolved once per request and cached in the request
 * attributes, so middleware and listeners share the same instance.
 */
class ResolveDevice
{
    /**
     * Request attribute holding the resolved device.
     */
    const REQUEST_KEY = 'auth_tracker.device';

    public function __construct(protected TrackerManager $tracker)
    {
    }

    public function execute(Request $request): Device
    {
        if ($device = $request->attributes->get(self::REQUEST_KEY)) {
            return $device;
        }

        $signal = $this->tracker->deviceSignal($request);

        if (! $signal->udid) {
            $signal = $signal->withUdid(Str::random(64));
        }

        $device = $this->tracker->deviceModel()::query()->firstOrNew(['udid' => $signal->udid]);

        $isNew = ! $device->exists;

        $device->mergeAttributes($signal->toArray() + ['last_seen_at' => now()]);

        // Expose the device headers to the rest of the pipeline.
        $this->fillHeaders($request, $signal);

        $request->attributes->set(self::REQUEST_KEY, $device);

        if ($isNew) {
            event(new DeviceRegistered($device, $request));
        }

        return $device;
    }

    /**
     * Device already resolved for this request, if any.
     */
    public static function current(?Request $request = null): ?Device
    {
        $request = $request ?? (app()->bound('request') ? request() : null);

        return $request?->attributes->get(self::REQUEST_KEY);
    }

    protected function fillHeaders(Request $request, DeviceSignal $signal): void
    {
        $headers = [
            'udid' => $signal->udid,
            'os' => $signal->os,
            'os-version' => $signal->osVersion,
            'manufacturer' => $signal->manufacturer,
            'model' => $signal->model,
            'fcm-token' => $signal->fcmToken,
            'app-version' => $signal->appVersion,
            'app-type' => $signal->appType,
        ];

        foreach ($headers as $name => $value) {
            $header = DeviceSignal::headerName($name);

            if ($value !== null && ! $request->hasHeader($header)) {
                $request->headers->set($header, $value);
            }
        }
    }
}
