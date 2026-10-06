<?php

namespace OwaisKit\AuthTracker\Events;

use OwaisKit\AuthTracker\Models\Device;
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
