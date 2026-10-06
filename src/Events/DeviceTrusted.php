<?php

namespace Awsan\AuthTracker\Events;

use Awsan\AuthTracker\Models\Device;

class DeviceTrusted
{
    public function __construct(public Device $device)
    {
    }
}
