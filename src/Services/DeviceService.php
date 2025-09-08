<?php

namespace Alshahari\AuthTracker\Services;

use Alshahari\AuthTracker\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

/**
 * Enhanced Device Service with Dynamic Configuration
 * 
 * This service handles device registration, management, and security
 * with configurable device attributes and validation rules.
 */
class DeviceService
{
    protected $request;
    protected $agent;
    protected $deviceUdid;
    protected $deviceAttributes = [];
    protected $requiredAttributes = [];
    protected $optionalAttributes = [];

    public function __construct()
    {
        $this->request = request();
        $this->agent = new Agent();
        $this->initializeConfiguration();
        $this->extractDeviceAttributes();
    }

    /**
     * Initialize device configuration from config
     */
    protected function initializeConfiguration(): void
    {
        $config = config('auth_tracker.device', []);
        
        // Required attributes (must be present)
        $this->requiredAttributes = $config['required_attributes'] ?? [
            'udid',
            'os',
            'manufacturer',
            'model'
        ];
        
        // Optional attributes (can be empty)
        $this->optionalAttributes = $config['optional_attributes'] ?? [
            'os_version',
            'fcm_token',
            'app_version',
            'app_type',
            'tenant',
            'user_agent',
            'screen_resolution',
            'timezone',
            'language',
            'battery_level',
            'network_type',
            'carrier'
        ];
        
        // All possible attributes
        $this->deviceAttributes = array_merge($this->requiredAttributes, $this->optionalAttributes);
    }

    /**
     * Extract device attributes from request
     */
    protected function extractDeviceAttributes(): void
    {
        $attributes = [];
        
        // Extract from headers
        foreach ($this->deviceAttributes as $attribute) {
            $headerKey = 'x-device-' . str_replace('_', '-', $attribute);
            $value = $this->request->header($headerKey);
            
            if ($value !== null) {
                $attributes[$attribute] = $value;
            }
        }
        
        // Extract from cookies as fallback
        foreach ($this->deviceAttributes as $attribute) {
            if (!isset($attributes[$attribute])) {
                $cookieKey = 'device_' . $attribute;
                $value = $this->request->cookie($cookieKey);
                
                if ($value !== null) {
                    $attributes[$attribute] = $value;
                }
            }
        }
        
        // Auto-detect missing attributes
        $this->autoDetectAttributes($attributes);
        
        // Generate UDID if not provided
        if (empty($attributes['udid'])) {
            $attributes['udid'] = $this->generateDeviceUdid();
        }
        
        $this->deviceAttributes = $attributes;
    }

    /**
     * Auto-detect device attributes using User Agent
     */
    protected function autoDetectAttributes(array &$attributes): void
    {
        // OS detection
        if (empty($attributes['os'])) {
            $platform = $this->agent->platform();
            if ($platform) {
                $attributes['os'] = $platform;
                $attributes['os_version'] = $this->agent->version($platform) ?: $attributes['os_version'] ?? null;
            }
        }
        
        // Device detection
        if (empty($attributes['manufacturer'])) {
            $device = $this->agent->device();
            if ($device && $device !== 'WebKit') {
                $attributes['manufacturer'] = $device;
            } else {
                $attributes['manufacturer'] = $this->agent->deviceType() ?: 'Unknown';
            }
        }
        
        if (empty($attributes['model'])) {
            $browser = $this->agent->browser();
            if ($browser) {
                $attributes['model'] = $browser;
            }
        }
        
        // App type detection
        if (empty($attributes['app_type'])) {
            $attributes['app_type'] = $this->detectAppType();
        }
        
        // User agent
        if (empty($attributes['user_agent'])) {
            $attributes['user_agent'] = $this->agent->getUserAgent();
        }
    }

    /**
     * Detect application type
     */
    protected function detectAppType(): string
    {
        if ($this->agent->isDesktop()) {
            return 'web_pc';
        }
        
        if ($this->agent->isMobile()) {
            return 'web_mobile';
        }
        
        if ($this->agent->isTablet()) {
            return 'web_tablet';
        }
        
        return 'other';
    }

    /**
     * Get or create device
     */
    public function getOrCreateDevice(array $context = []): Device
    {
        $udid = $this->deviceAttributes['udid'] ?? $this->generateDeviceUdid();
        
        // Check if device exists
        $device = $this->findDeviceByUdid($udid);
        
        if (!$device) {
            $device = $this->createDevice($context);
        } else {
            $device = $this->updateDevice($device, $context);
        }
        
        return $device;
    }

    /**
     * Create new device
     */
    public function createDevice(array $context = []): Device
    {
        $deviceData = array_merge($this->deviceAttributes, $context);
        
        // Validate required attributes
        $this->validateDeviceAttributes($deviceData);
        
        // Generate device token for security
        $deviceData['device_token'] = $this->generateDeviceToken();
        $deviceData['client_id'] = $this->generateClientId();
        $deviceData['is_active'] = true;
        $deviceData['last_seen_at'] = now();
        
        $device = Device::create($deviceData);
        
        Log::info('Device created', [
            'device_id' => $device->id,
            'udid' => $device->udid,
            'client_id' => $device->client_id
        ]);
        
        return $device;
    }

    /**
     * Update existing device
     */
    public function updateDevice(Device $device, array $context = []): Device
    {
        $updateData = array_merge($this->deviceAttributes, $context);
        
        // Update only changed attributes
        $changedAttributes = [];
        foreach ($updateData as $key => $value) {
            if ($device->$key !== $value) {
                $changedAttributes[$key] = $value;
            }
        }
        
        if (!empty($changedAttributes)) {
            $changedAttributes['last_seen_at'] = now();
            $device->update($changedAttributes);
            
            Log::info('Device updated', [
                'device_id' => $device->id,
                'udid' => $device->udid,
                'updated_attributes' => array_keys($changedAttributes)
            ]);
        }
        
        return $device;
    }

    /**
     * Find device by UDID
     */
    public function findDeviceByUdid(string $udid): ?Device
    {
        return Device::where('udid', $udid)->first();
    }

    /**
     * Find device by client ID
     */
    public function findDeviceByClientId(string $clientId): ?Device
    {
        return Device::where('client_id', $clientId)->first();
    }

    /**
     * Validate device attributes
     */
    protected function validateDeviceAttributes(array $attributes): void
    {
        foreach ($this->requiredAttributes as $attribute) {
            if (empty($attributes[$attribute])) {
                throw new \InvalidArgumentException("Required device attribute '{$attribute}' is missing");
            }
        }
        
        // Validate UDID format
        if (isset($attributes['udid']) && !$this->isValidUdid($attributes['udid'])) {
            throw new \InvalidArgumentException("Invalid UDID format");
        }
        
        // Validate OS
        if (isset($attributes['os']) && !$this->isValidOs($attributes['os'])) {
            throw new \InvalidArgumentException("Invalid OS: {$attributes['os']}");
        }
    }

    /**
     * Check if UDID is valid
     */
    protected function isValidUdid(string $udid): bool
    {
        return strlen($udid) >= 10 && strlen($udid) <= 100;
    }

    /**
     * Check if OS is valid
     */
    protected function isValidOs(string $os): bool
    {
        $validOs = config('auth_tracker.device.valid_os', [
            'android', 'ios', 'windows', 'macos', 'linux', 'web'
        ]);
        
        return in_array(strtolower($os), $validOs);
    }

    /**
     * Generate device UDID
     */
    public function generateDeviceUdid(): string
    {
        $uniqueId = uniqid('', true);
        $timestamp = time();
        $userAgent = $this->agent->getUserAgent();
        $numericPart = preg_replace('/[^0-9]/', '', $userAgent);
        
        return $timestamp . $numericPart . $uniqueId;
    }

    /**
     * Generate device token for security
     */
    public function generateDeviceToken(): string
    {
        return Hash::make(Str::random(60) . time());
    }

    /**
     * Generate client ID
     */
    public function generateClientId(): string
    {
        return 'client_' . Str::random(32);
    }

    /**
     * Refresh device token
     */
    public function refreshDeviceToken(Device $device): string
    {
        $newToken = $this->generateDeviceToken();
        $device->update([
            'device_token' => $newToken,
            'token_refreshed_at' => now()
        ]);
        
        Log::info('Device token refreshed', [
            'device_id' => $device->id,
            'udid' => $device->udid
        ]);
        
        return $newToken;
    }

    /**
     * Validate device token
     */
    public function validateDeviceToken(Device $device, string $token): bool
    {
        return Hash::check($token, $device->device_token);
    }

    /**
     * Get device attributes
     */
    public function getDeviceAttributes(): array
    {
        return $this->deviceAttributes;
    }

    /**
     * Set device attribute
     */
    public function setDeviceAttribute(string $key, $value): void
    {
        if (in_array($key, $this->deviceAttributes)) {
            $this->deviceAttributes[$key] = $value;
        }
    }

    /**
     * Get device attribute
     */
    public function getDeviceAttribute(string $key)
    {
        return $this->deviceAttributes[$key] ?? null;
    }

    /**
     * Get device UDID
     */
    public function getDeviceUdid(): string
    {
        return $this->deviceAttributes['udid'] ?? $this->generateDeviceUdid();
    }

    /**
     * Check if device is trusted
     */
    public function isDeviceTrusted(Device $device): bool
    {
        return $device->is_trusted ?? false;
    }

    /**
     * Mark device as trusted
     */
    public function markDeviceAsTrusted(Device $device): bool
    {
        return $device->update(['is_trusted' => true, 'trusted_at' => now()]);
    }

    /**
     * Mark device as untrusted
     */
    public function markDeviceAsUntrusted(Device $device): bool
    {
        return $device->update(['is_trusted' => false, 'trusted_at' => null]);
    }

    /**
     * Get device statistics
     */
    public function getDeviceStatistics(): array
    {
        return [
            'total_devices' => Device::count(),
            'active_devices' => Device::where('is_active', true)->count(),
            'trusted_devices' => Device::where('is_trusted', true)->count(),
            'devices_by_os' => Device::groupBy('os')->selectRaw('os, count(*) as count')->get(),
            'devices_by_manufacturer' => Device::groupBy('manufacturer')->selectRaw('manufacturer, count(*) as count')->get(),
        ];
    }
}