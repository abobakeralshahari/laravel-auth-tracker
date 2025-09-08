<?php

namespace Alshahari\AuthTracker\Services;

use Alshahari\AuthTracker\Models\Device;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Security Service for Auth Tracker
 * 
 * Handles client ID generation, device token management,
 * API protection, and security validations.
 */
class SecurityService
{
    protected $config;

    public function __construct()
    {
        $this->config = config('auth_tracker.security', []);
    }

    /**
     * Generate client ID for device
     *
     * @param Device $device
     * @return string
     */
    public function generateClientId(Device $device): string
    {
        $clientId = 'client_' . Str::random(32) . '_' . time();
        
        $device->update(['client_id' => $clientId]);
        
        Log::info('Client ID generated', [
            'device_id' => $device->id,
            'client_id' => $clientId
        ]);
        
        return $clientId;
    }

    /**
     * Generate device token
     *
     * @param Device $device
     * @return string
     */
    public function generateDeviceToken(Device $device): string
    {
        $token = Str::random(60);
        $hashedToken = Hash::make($token);
        
        $device->update([
            'device_token' => $hashedToken,
            'token_generated_at' => now(),
            'token_expires_at' => now()->addDays($this->getTokenLifetime())
        ]);
        
        Log::info('Device token generated', [
            'device_id' => $device->id,
            'token_expires_at' => $device->token_expires_at
        ]);
        
        return $token;
    }

    /**
     * Refresh device token
     *
     * @param Device $device
     * @param string $currentToken
     * @return string|null
     */
    public function refreshDeviceToken(Device $device, string $currentToken): ?string
    {
        // Validate current token
        if (!$this->validateDeviceToken($device, $currentToken)) {
            Log::warning('Invalid token provided for refresh', [
                'device_id' => $device->id,
                'ip' => request()->ip()
            ]);
            return null;
        }

        // Check if token can be refreshed
        if (!$this->canRefreshToken($device)) {
            Log::warning('Token refresh not allowed', [
                'device_id' => $device->id,
                'last_refresh' => $device->token_refreshed_at
            ]);
            return null;
        }

        // Generate new token
        $newToken = $this->generateDeviceToken($device);
        
        // Update refresh timestamp
        $device->update(['token_refreshed_at' => now()]);
        
        Log::info('Device token refreshed', [
            'device_id' => $device->id,
            'new_token_expires_at' => $device->token_expires_at
        ]);
        
        return $newToken;
    }

    /**
     * Validate device token
     *
     * @param Device $device
     * @param string $token
     * @return bool
     */
    public function validateDeviceToken(Device $device, string $token): bool
    {
        // Check if token exists
        if (!$device->device_token) {
            return false;
        }

        // Check if token is expired
        if ($device->token_expires_at && $device->token_expires_at->isPast()) {
            Log::info('Device token expired', [
                'device_id' => $device->id,
                'expires_at' => $device->token_expires_at
            ]);
            return false;
        }

        // Check if device is active
        if (!$device->is_active) {
            Log::warning('Inactive device attempted token validation', [
                'device_id' => $device->id
            ]);
            return false;
        }

        // Validate token hash
        return Hash::check($token, $device->device_token);
    }

    /**
     * Validate client ID
     *
     * @param string $clientId
     * @return Device|null
     */
    public function validateClientId(string $clientId): ?Device
    {
        $device = Device::where('client_id', $clientId)->first();
        
        if (!$device) {
            Log::warning('Invalid client ID provided', [
                'client_id' => $clientId,
                'ip' => request()->ip()
            ]);
            return null;
        }

        // Check if device is active
        if (!$device->is_active) {
            Log::warning('Inactive device with client ID', [
                'device_id' => $device->id,
                'client_id' => $clientId
            ]);
            return null;
        }

        return $device;
    }

    /**
     * Validate API request
     *
     * @param string $clientId
     * @param string $deviceToken
     * @return Device|null
     */
    public function validateApiRequest(string $clientId, string $deviceToken): ?Device
    {
        $device = $this->validateClientId($clientId);
        
        if (!$device) {
            return null;
        }

        if (!$this->validateDeviceToken($device, $deviceToken)) {
            return null;
        }

        // Update last seen
        $device->update(['last_seen_at' => now()]);
        
        return $device;
    }

    /**
     * Check if token can be refreshed
     *
     * @param Device $device
     * @return bool
     */
    protected function canRefreshToken(Device $device): bool
    {
        $refreshInterval = $this->config['token_refresh_interval'] ?? 3600; // 1 hour
        
        if (!$device->token_refreshed_at) {
            return true;
        }

        return $device->token_refreshed_at->addSeconds($refreshInterval)->isPast();
    }

    /**
     * Get token lifetime in days
     *
     * @return int
     */
    protected function getTokenLifetime(): int
    {
        return $this->config['token_lifetime_days'] ?? 30;
    }

    /**
     * Revoke device token
     *
     * @param Device $device
     * @return bool
     */
    public function revokeDeviceToken(Device $device): bool
    {
        $result = $device->update([
            'device_token' => null,
            'token_generated_at' => null,
            'token_expires_at' => null,
            'is_active' => false
        ]);

        Log::info('Device token revoked', [
            'device_id' => $device->id,
            'client_id' => $device->client_id
        ]);

        return $result;
    }

    /**
     * Revoke all device tokens for user
     *
     * @param int $userId
     * @return int
     */
    public function revokeAllUserTokens(int $userId): int
    {
        $devices = Device::whereHas('deviceable', function ($query) use ($userId) {
            $query->where('id', $userId);
        })->get();

        $revokedCount = 0;
        foreach ($devices as $device) {
            if ($this->revokeDeviceToken($device)) {
                $revokedCount++;
            }
        }

        Log::info('All user tokens revoked', [
            'user_id' => $userId,
            'revoked_count' => $revokedCount
        ]);

        return $revokedCount;
    }

    /**
     * Generate API key for device
     *
     * @param Device $device
     * @return string
     */
    public function generateApiKey(Device $device): string
    {
        $apiKey = 'ak_' . Str::random(40);
        $hashedApiKey = Hash::make($apiKey);
        
        $device->update([
            'api_key' => $hashedApiKey,
            'api_key_generated_at' => now(),
            'api_key_expires_at' => now()->addDays($this->getApiKeyLifetime())
        ]);
        
        Log::info('API key generated', [
            'device_id' => $device->id,
            'api_key_expires_at' => $device->api_key_expires_at
        ]);
        
        return $apiKey;
    }

    /**
     * Validate API key
     *
     * @param string $apiKey
     * @return Device|null
     */
    public function validateApiKey(string $apiKey): ?Device
    {
        $devices = Device::whereNotNull('api_key')->get();
        
        foreach ($devices as $device) {
            if (Hash::check($apiKey, $device->api_key)) {
                // Check if API key is expired
                if ($device->api_key_expires_at && $device->api_key_expires_at->isPast()) {
                    continue;
                }
                
                // Check if device is active
                if (!$device->is_active) {
                    continue;
                }
                
                return $device;
            }
        }
        
        Log::warning('Invalid API key provided', [
            'api_key' => substr($apiKey, 0, 10) . '...',
            'ip' => request()->ip()
        ]);
        
        return null;
    }

    /**
     * Get API key lifetime in days
     *
     * @return int
     */
    protected function getApiKeyLifetime(): int
    {
        return $this->config['api_key_lifetime_days'] ?? 90;
    }

    /**
     * Check rate limit for device
     *
     * @param Device $device
     * @param string $action
     * @return bool
     */
    public function checkRateLimit(Device $device, string $action): bool
    {
        $rateLimits = $this->config['rate_limits'] ?? [];
        $limit = $rateLimits[$action] ?? 100; // Default 100 requests per hour
        
        $key = "rate_limit:{$device->id}:{$action}";
        $current = cache()->get($key, 0);
        
        if ($current >= $limit) {
            Log::warning('Rate limit exceeded', [
                'device_id' => $device->id,
                'action' => $action,
                'current' => $current,
                'limit' => $limit
            ]);
            return false;
        }
        
        cache()->put($key, $current + 1, 3600); // 1 hour
        return true;
    }

    /**
     * Log security event
     *
     * @param string $event
     * @param array $data
     * @return void
     */
    public function logSecurityEvent(string $event, array $data = []): void
    {
        $logData = array_merge([
            'event' => $event,
            'timestamp' => now()->toISOString(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $data);
        
        Log::channel('security')->info('Security event', $logData);
    }

    /**
     * Get security statistics
     *
     * @return array
     */
    public function getSecurityStatistics(): array
    {
        return [
            'total_devices' => Device::count(),
            'active_devices' => Device::where('is_active', true)->count(),
            'devices_with_tokens' => Device::whereNotNull('device_token')->count(),
            'devices_with_api_keys' => Device::whereNotNull('api_key')->count(),
            'expired_tokens' => Device::where('token_expires_at', '<', now())->count(),
            'expired_api_keys' => Device::where('api_key_expires_at', '<', now())->count(),
        ];
    }
}
