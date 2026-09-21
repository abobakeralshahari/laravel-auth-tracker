<?php

namespace Alshahari\AuthTracker\Events;

use Alshahari\AuthTracker\Models\Device;

class DeviceTrusted
{
    public function __construct(public Device $device)
    {
    }
}
