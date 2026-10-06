<?php

namespace OwaisKit\AuthTracker\Events;

use OwaisKit\AuthTracker\Models\Device;

class DeviceTrusted
{
    public function __construct(public Device $device)
    {
    }
}
