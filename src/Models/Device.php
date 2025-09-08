<?php

namespace Alshahari\AuthTracker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * Device Model
 * 
 * Represents a device used for authentication tracking
 * with enhanced security features and dynamic configuration.
 */
class Device extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'udid',
        'os',
        'os_version',
        'manufacturer',
        'model',
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
        'carrier',
        'client_id',
        'device_token',
        'api_key',
        'is_active',
        'is_trusted',
        'trusted_at',
        'token_generated_at',
        'token_expires_at',
        'token_refreshed_at',
        'api_key_generated_at',
        'api_key_expires_at',
        'last_seen_at',
    ];

    protected $hidden = [
        'device_token',
        'api_key',
        'fcm_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_trusted' => 'boolean',
        'trusted_at' => 'datetime',
        'token_generated_at' => 'datetime',
        'token_expires_at' => 'datetime',
        'token_refreshed_at' => 'datetime',
        'api_key_generated_at' => 'datetime',
        'api_key_expires_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        $this->setConnection(config('auth_tracker.connection'));
        parent::__construct($attributes);
    }
    
    /**
     * Get the deviceable model (user, admin, etc.)
     */
    public function deviceable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get all logins for this device
     */
    public function logins(): HasMany
    {
        return $this->hasMany(Login::class, 'device_id', 'id');
    }

    /**
     * Get the latest login for this device
     */
    public function latestLogin(): HasOne
    {
        return $this->hasOne(Login::class, 'device_id', 'id')
                    ->latest();
    }

    /**
     * Get active logins for this device
     */
    public function activeLogins(): HasMany
    {
        return $this->hasMany(Login::class, 'device_id', 'id')
                    ->where('logout_at', null)
                    ->where('cleared_by_user', false);
    }

    /**
     * Check if device token is expired
     */
    public function isTokenExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    /**
     * Check if API key is expired
     */
    public function isApiKeyExpired(): bool
    {
        return $this->api_key_expires_at && $this->api_key_expires_at->isPast();
    }

    /**
     * Check if device is online (seen within last 5 minutes)
     */
    public function isOnline(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->isAfter(now()->subMinutes(5));
    }

    /**
     * Get device display name
     */
    public function getDisplayNameAttribute(): string
    {
        $parts = array_filter([
            $this->manufacturer,
            $this->model,
            $this->os
        ]);
        
        return implode(' ', $parts) ?: 'Unknown Device';
    }

    /**
     * Get device type (mobile, desktop, tablet)
     */
    public function getDeviceTypeAttribute(): string
    {
        if (in_array($this->os, ['android', 'ios'])) {
            return 'mobile';
        }
        
        if (in_array($this->os, ['windows', 'macos', 'linux'])) {
            return 'desktop';
        }
        
        return 'unknown';
    }

    /**
     * Get device status
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }
        
        if ($this->isTokenExpired()) {
            return 'token_expired';
        }
        
        if ($this->isOnline()) {
            return 'online';
        }
        
        return 'offline';
    }

    /**
     * Scope for active devices
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for trusted devices
     */
    public function scopeTrusted($query)
    {
        return $query->where('is_trusted', true);
    }

    /**
     * Scope for devices with valid tokens
     */
    public function scopeWithValidTokens($query)
    {
        return $query->whereNotNull('device_token')
                    ->where('token_expires_at', '>', now());
    }

    /**
     * Scope for online devices
     */
    public function scopeOnline($query)
    {
        return $query->where('last_seen_at', '>', now()->subMinutes(5));
    }

    /**
     * Scope for devices by OS
     */
    public function scopeByOs($query, string $os)
    {
        return $query->where('os', $os);
    }

    /**
     * Scope for devices by manufacturer
     */
    public function scopeByManufacturer($query, string $manufacturer)
    {
        return $query->where('manufacturer', $manufacturer);
    }

    /**
     * Get device statistics
     */
    public static function getStatistics(): array
    {
        return [
            'total' => self::count(),
            'active' => self::active()->count(),
            'trusted' => self::trusted()->count(),
            'online' => self::online()->count(),
            'by_os' => self::groupBy('os')->selectRaw('os, count(*) as count')->get(),
            'by_manufacturer' => self::groupBy('manufacturer')->selectRaw('manufacturer, count(*) as count')->get(),
        ];
    }
}
