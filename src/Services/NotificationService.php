<?php

namespace Alshahari\AuthTracker\Services;

use Alshahari\AuthTracker\Models\Device;
use Alshahari\AuthTracker\Models\Login;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

/**
 * Notification Service for Auth Tracker
 * 
 * Handles sending notifications for various authentication events
 * including new logins, suspicious activities, and device registrations.
 */
class NotificationService
{
    protected $config;

    public function __construct()
    {
        $this->config = config('auth_tracker.notifications', []);
    }

    /**
     * Send login notification
     *
     * @param Authenticatable $user
     * @param Device $device
     * @param Login $login
     * @return void
     */
    public function sendLoginNotification(Authenticatable $user, Device $device, Login $login): void
    {
        if (!$this->isNotificationEnabled('login')) {
            return;
        }

        try {
            // Check if this is a new device
            $isNewDevice = $this->isNewDeviceForUser($user, $device);
            
            // Check if this is suspicious login
            $isSuspicious = $this->isSuspiciousLogin($user, $device, $login);

            if ($isNewDevice) {
                $this->sendNewDeviceNotification($user, $device, $login);
            }

            if ($isSuspicious) {
                $this->sendSuspiciousLoginNotification($user, $device, $login);
            }

            // Send general login notification if enabled
            if ($this->isNotificationEnabled('general_login')) {
                $this->sendGeneralLoginNotification($user, $device, $login);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send login notification', [
                'user_id' => $user->id,
                'device_id' => $device->id,
                'login_id' => $login->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send new device notification
     *
     * @param Authenticatable $user
     * @param Device $device
     * @param Login $login
     * @return void
     */
    public function sendNewDeviceNotification(Authenticatable $user, Device $device, Login $login): void
    {
        $notificationData = [
            'user' => $user,
            'device' => $device,
            'login' => $login,
            'login_time' => $login->created_at,
            'location' => $this->getLocationString($login),
            'ip_address' => $login->ip,
        ];

        // Send email notification
        if ($this->isChannelEnabled('email')) {
            $this->sendEmailNotification($user, 'new_device', $notificationData);
        }

        // Send push notification
        if ($this->isChannelEnabled('push') && $device->fcm_token) {
            $this->sendPushNotification($device, 'new_device', $notificationData);
        }

        // Send database notification
        if ($this->isChannelEnabled('database')) {
            $this->sendDatabaseNotification($user, 'new_device', $notificationData);
        }

        // Send webhook
        if ($this->isChannelEnabled('webhook')) {
            $this->sendWebhookNotification('new_device', $notificationData);
        }
    }

    /**
     * Send suspicious login notification
     *
     * @param Authenticatable $user
     * @param Device $device
     * @param Login $login
     * @return void
     */
    public function sendSuspiciousLoginNotification(Authenticatable $user, Device $device, Login $login): void
    {
        $notificationData = [
            'user' => $user,
            'device' => $device,
            'login' => $login,
            'login_time' => $login->created_at,
            'location' => $this->getLocationString($login),
            'ip_address' => $login->ip,
            'suspicious_reasons' => $this->getSuspiciousReasons($user, $device, $login),
        ];

        // Send email notification
        if ($this->isChannelEnabled('email')) {
            $this->sendEmailNotification($user, 'suspicious_login', $notificationData);
        }

        // Send push notification
        if ($this->isChannelEnabled('push') && $device->fcm_token) {
            $this->sendPushNotification($device, 'suspicious_login', $notificationData);
        }

        // Send database notification
        if ($this->isChannelEnabled('database')) {
            $this->sendDatabaseNotification($user, 'suspicious_login', $notificationData);
        }

        // Send webhook
        if ($this->isChannelEnabled('webhook')) {
            $this->sendWebhookNotification('suspicious_login', $notificationData);
        }
    }

    /**
     * Send general login notification
     *
     * @param Authenticatable $user
     * @param Device $device
     * @param Login $login
     * @return void
     */
    public function sendGeneralLoginNotification(Authenticatable $user, Device $device, Login $login): void
    {
        $notificationData = [
            'user' => $user,
            'device' => $device,
            'login' => $login,
            'login_time' => $login->created_at,
            'location' => $this->getLocationString($login),
            'ip_address' => $login->ip,
        ];

        // Send push notification
        if ($this->isChannelEnabled('push') && $device->fcm_token) {
            $this->sendPushNotification($device, 'general_login', $notificationData);
        }
    }

    /**
     * Send device registration notification
     *
     * @param Authenticatable $user
     * @param Device $device
     * @return void
     */
    public function sendDeviceRegistrationNotification(Authenticatable $user, Device $device): void
    {
        if (!$this->isNotificationEnabled('device_registration')) {
            return;
        }

        $notificationData = [
            'user' => $user,
            'device' => $device,
            'registration_time' => $device->created_at,
        ];

        // Send email notification
        if ($this->isChannelEnabled('email')) {
            $this->sendEmailNotification($user, 'device_registration', $notificationData);
        }

        // Send database notification
        if ($this->isChannelEnabled('database')) {
            $this->sendDatabaseNotification($user, 'device_registration', $notificationData);
        }
    }

    /**
     * Send email notification
     *
     * @param Authenticatable $user
     * @param string $type
     * @param array $data
     * @return void
     */
    protected function sendEmailNotification(Authenticatable $user, string $type, array $data): void
    {
        $emailClass = $this->getEmailClass($type);
        
        if ($emailClass) {
            Mail::to($user->email)->send(new $emailClass($data));
        }
    }

    /**
     * Send push notification
     *
     * @param Device $device
     * @param string $type
     * @param array $data
     * @return void
     */
    protected function sendPushNotification(Device $device, string $type, array $data): void
    {
        $pushService = app(PushNotificationService::class);
        $pushService->send($device->fcm_token, $this->getPushMessage($type, $data));
    }

    /**
     * Send database notification
     *
     * @param Authenticatable $user
     * @param string $type
     * @param array $data
     * @return void
     */
    protected function sendDatabaseNotification(Authenticatable $user, string $type, array $data): void
    {
        $notificationClass = $this->getNotificationClass($type);
        
        if ($notificationClass) {
            $user->notify(new $notificationClass($data));
        }
    }

    /**
     * Send webhook notification
     *
     * @param string $type
     * @param array $data
     * @return void
     */
    protected function sendWebhookNotification(string $type, array $data): void
    {
        $webhookUrl = $this->config['webhook_url'] ?? null;
        
        if (!$webhookUrl) {
            return;
        }

        $payload = [
            'type' => $type,
            'timestamp' => now()->toISOString(),
            'data' => $data
        ];

        // Send webhook asynchronously
        dispatch(new SendWebhookJob($webhookUrl, $payload));
    }

    /**
     * Check if notification is enabled
     *
     * @param string $type
     * @return bool
     */
    protected function isNotificationEnabled(string $type): bool
    {
        return $this->config['enabled'] ?? false && 
               ($this->config['types'][$type] ?? false);
    }

    /**
     * Check if channel is enabled
     *
     * @param string $channel
     * @return bool
     */
    protected function isChannelEnabled(string $channel): bool
    {
        return $this->config['channels'][$channel] ?? false;
    }

    /**
     * Check if device is new for user
     *
     * @param Authenticatable $user
     * @param Device $device
     * @return bool
     */
    protected function isNewDeviceForUser(Authenticatable $user, Device $device): bool
    {
        return !$user->devices()->where('id', $device->id)->exists();
    }

    /**
     * Check if login is suspicious
     *
     * @param Authenticatable $user
     * @param Device $device
     * @param Login $login
     * @return bool
     */
    protected function isSuspiciousLogin(Authenticatable $user, Device $device, Login $login): bool
    {
        // Check for new device
        $isNewDevice = $this->isNewDeviceForUser($user, $device);
        
        // Check for different location
        $lastLogin = $user->logins()->latest()->first();
        $isDifferentLocation = $lastLogin && 
            $lastLogin->country !== $login->country;
        
        // Check for unusual time
        $currentHour = now()->hour;
        $isUnusualTime = $currentHour < 6 || $currentHour > 22;
        
        return $isNewDevice || $isDifferentLocation || $isUnusualTime;
    }

    /**
     * Get location string
     *
     * @param Login $login
     * @return string
     */
    protected function getLocationString(Login $login): string
    {
        $location = array_filter([
            $login->city,
            $login->region,
            $login->country
        ]);
        
        return implode(', ', $location) ?: 'Unknown Location';
    }

    /**
     * Get suspicious reasons
     *
     * @param Authenticatable $user
     * @param Device $device
     * @param Login $login
     * @return array
     */
    protected function getSuspiciousReasons(Authenticatable $user, Device $device, Login $login): array
    {
        $reasons = [];
        
        if ($this->isNewDeviceForUser($user, $device)) {
            $reasons[] = 'New device detected';
        }
        
        $lastLogin = $user->logins()->latest()->first();
        if ($lastLogin && $lastLogin->country !== $login->country) {
            $reasons[] = 'Different location';
        }
        
        $currentHour = now()->hour;
        if ($currentHour < 6 || $currentHour > 22) {
            $reasons[] = 'Unusual time';
        }
        
        return $reasons;
    }

    /**
     * Get email class for notification type
     *
     * @param string $type
     * @return string|null
     */
    protected function getEmailClass(string $type): ?string
    {
        $emailClasses = $this->config['email_classes'] ?? [];
        return $emailClasses[$type] ?? null;
    }

    /**
     * Get notification class for notification type
     *
     * @param string $type
     * @return string|null
     */
    protected function getNotificationClass(string $type): ?string
    {
        $notificationClasses = $this->config['notification_classes'] ?? [];
        return $notificationClasses[$type] ?? null;
    }

    /**
     * Get push message for notification type
     *
     * @param string $type
     * @param array $data
     * @return array
     */
    protected function getPushMessage(string $type, array $data): array
    {
        $messages = $this->config['push_messages'] ?? [];
        $message = $messages[$type] ?? 'New login detected';
        
        return [
            'title' => 'Auth Tracker',
            'body' => $message,
            'data' => $data
        ];
    }
}
