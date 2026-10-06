<?php

namespace Awsan\AuthTracker\Events;

use Awsan\AuthTracker\Models\Device;
use Illuminate\Http\Request;

/**
 * A device was seen for the first time.
 */
class DeviceRegistered
{
    public function __construct(
        public Device $device,
        public Request $request,
    ) {
    }
}
