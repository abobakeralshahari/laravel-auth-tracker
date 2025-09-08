<?php

namespace Alshahari\AuthTracker\Events;

use Alshahari\AuthTracker\Models\Device;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Device Registered Event
 * 
 * This event is fired when a new device is registered for a user.
 * It can be used to trigger notifications or security measures.
 */
class DeviceRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $device;
    public $context;

    /**
     * Create a new event instance.
     *
     * @param Authenticatable $user
     * @param Device $device
     * @param array $context
     */
    public function __construct(Authenticatable $user, Device $device, array $context = [])
    {
        $this->user = $user;
        $this->device = $device;
        $this->context = $context;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->user->id);
    }
}
