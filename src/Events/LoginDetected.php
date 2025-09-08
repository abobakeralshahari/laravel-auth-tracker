<?php

namespace Alshahari\AuthTracker\Events;

use Alshahari\AuthTracker\Models\Device;
use Alshahari\AuthTracker\Models\Login;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Login Detected Event
 * 
 * This event is fired when a user login is detected and tracked.
 * It can be used to trigger notifications, logging, or other actions.
 */
class LoginDetected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $device;
    public $login;
    public $context;

    /**
     * Create a new event instance.
     *
     * @param Authenticatable $user
     * @param Device $device
     * @param Login $login
     * @param array $context
     */
    public function __construct(Authenticatable $user, Device $device, Login $login, array $context = [])
    {
        $this->user = $user;
        $this->device = $device;
        $this->login = $login;
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
