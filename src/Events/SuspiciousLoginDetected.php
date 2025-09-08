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
 * Suspicious Login Detected Event
 * 
 * This event is fired when a suspicious login is detected.
 * Suspicious logins include new devices, different locations, or unusual times.
 */
class SuspiciousLoginDetected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $device;
    public $login;
    public $context;
    public $reasons;

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
        $this->reasons = $this->determineReasons($user, $device, $login);
    }

    /**
     * Determine the reasons for suspicious activity
     *
     * @param Authenticatable $user
     * @param Device $device
     * @param Login $login
     * @return array
     */
    protected function determineReasons(Authenticatable $user, Device $device, Login $login): array
    {
        $reasons = [];
        
        // Check for new device
        if (!$user->devices()->where('id', $device->id)->exists()) {
            $reasons[] = 'new_device';
        }
        
        // Check for different location
        $lastLogin = $user->logins()->latest()->first();
        if ($lastLogin && $lastLogin->country !== $login->country) {
            $reasons[] = 'different_location';
        }
        
        // Check for unusual time
        $currentHour = now()->hour;
        if ($currentHour < 6 || $currentHour > 22) {
            $reasons[] = 'unusual_time';
        }
        
        // Check for different IP pattern
        if ($lastLogin && $lastLogin->ip !== $login->ip) {
            $reasons[] = 'different_ip';
        }
        
        return $reasons;
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
