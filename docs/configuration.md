# Configuration Guide

## 📋 Overview

This guide covers all configuration options available in Laravel Auth Tracker.

## ⚙️ Basic Configuration

### Table Configuration

```php
// config/auth_tracker.php

// Table name for storing logins
'table_name' => 'logins',

// Remember token lifetime in days
'remember_lifetime' => 365,

// Device model class
'device_model' => Alshahari\AuthTracker\Models\Device::class,
```

## 🔧 Device Configuration

### Required Attributes

```php
'device' => [
    'required_attributes' => [
        'udid',           // Unique device identifier
        'os',             // Operating system
        'manufacturer',   // Device manufacturer
        'model'           // Device model
    ],
],
```

### Optional Attributes

```php
'device' => [
    'optional_attributes' => [
        'os_version',        // OS version
        'fcm_token',         // Firebase Cloud Messaging token
        'app_version',       // Application version
        'app_type',          // Application type
        'tenant',            // Tenant identifier
        'user_agent',        // User agent string
        'screen_resolution', // Screen resolution
        'timezone',          // Device timezone
        'language',          // Device language
        'battery_level',     // Battery level
        'network_type',      // Network type
        'carrier'            // Mobile carrier
    ],
],
```

### Valid Operating Systems

```php
'device' => [
    'valid_os' => [
        'android',
        'ios',
        'windows',
        'macos',
        'linux',
        'web'
    ],
],
```

### Device Behavior

```php
'device' => [
    'auto_detect' => true,        // Auto-detect missing attributes
    'trust_new_devices' => false, // Trust new devices by default
],
```

## 🛡️ Security Configuration

### Token Management

```php
'security' => [
    'token_lifetime_days' => 30,      // Device token lifetime
    'api_key_lifetime_days' => 90,    // API key lifetime
    'token_refresh_interval' => 3600, // Token refresh interval (seconds)
],
```

### Rate Limiting

```php
'security' => [
    'rate_limits' => [
        'login' => 10,           // Login attempts per hour
        'token_refresh' => 5,    // Token refreshes per hour
        'api_request' => 100,    // API requests per hour
    ],
],
```

### Security Requirements

```php
'security' => [
    'require_client_id' => true,           // Require client ID
    'require_device_token' => true,        // Require device token
    'auto_revoke_expired_tokens' => true,  // Auto-revoke expired tokens
],
```

## 🔔 Notification Configuration

### Notification Types

```php
'notifications' => [
    'enabled' => true,
    
    'types' => [
        'login' => true,              // General login notifications
        'new_device' => true,         // New device notifications
        'suspicious_login' => true,   // Suspicious activity notifications
        'device_registration' => true, // Device registration notifications
        'general_login' => false,     // General login notifications
    ],
],
```

### Notification Channels

```php
'notifications' => [
    'channels' => [
        'email' => true,      // Email notifications
        'push' => true,       // Push notifications
        'database' => true,   // Database notifications
        'webhook' => false,   // Webhook notifications
    ],
],
```

### Email Classes

```php
'notifications' => [
    'email_classes' => [
        'new_device' => \App\Notifications\NewDeviceLogin::class,
        'suspicious_login' => \App\Notifications\SuspiciousLogin::class,
        'device_registration' => \App\Notifications\DeviceRegistered::class,
    ],
],
```

### Notification Classes

```php
'notifications' => [
    'notification_classes' => [
        'new_device' => \App\Notifications\NewDeviceLoginNotification::class,
        'suspicious_login' => \App\Notifications\SuspiciousLoginNotification::class,
        'device_registration' => \App\Notifications\DeviceRegisteredNotification::class,
    ],
],
```

### Push Messages

```php
'notifications' => [
    'push_messages' => [
        'new_device' => 'New device login detected',
        'suspicious_login' => 'Suspicious login activity detected',
        'device_registration' => 'New device registered',
        'general_login' => 'Login successful',
    ],
],
```

### Webhook Configuration

```php
'notifications' => [
    'webhook_url' => 'https://your-webhook-url.com/endpoint',
],
```

## 🌍 IP Lookup Configuration

### Provider Selection

```php
'ip_lookup' => [
    'provider' => 'ip-api', // or 'ip2location-lite' or false
    'timeout' => 5.0,       // Request timeout in seconds
    'environments' => ['production'], // Environments to enable lookup
],
```

### Custom Providers

```php
'ip_lookup' => [
    'custom_providers' => [
        'my-provider' => App\Providers\MyIpProvider::class,
    ],
],
```

### Ip2Location Configuration

```php
'ip_lookup' => [
    'ip2location' => [
        'ipv4_table' => 'ip2location_db3',
        'ipv6_table' => 'ip2location_db3_ipv6',
    ],
],
```

## 🔧 Service Configuration

### Service Behavior

```php
'service' => [
    'auto_track_logins' => true,     // Auto-track logins
    'auto_register_devices' => true, // Auto-register devices
    'middleware_priority' => 100,    // Middleware priority
    'log_events' => true,            // Log events
    'cleanup_old_logs' => true,      // Cleanup old logs
    'cleanup_after_days' => 90,      // Days before cleanup
],
```

## 📊 Parser Configuration

### User Agent Parser

```php
'parser' => 'whichbrowser', // or 'agent'
```

### Supported Parsers

- **whichbrowser**: [WhichBrowser Parser](https://github.com/WhichBrowser/Parser-PHP)
- **agent**: [Agent Parser](https://github.com/jenssegers/agent)

## 🗄️ Database Configuration

### Connection

```php
'connection' => null, // Use default connection or specify custom
```

### Migration Configuration

```php
// Customize table names
'table_name' => 'user_logins', // Default: 'logins'
```

## 🔧 Advanced Configuration Examples

### Multi-Tenant Setup

```php
'device' => [
    'optional_attributes' => [
        'tenant', // Add tenant support
        'organization_id',
        'department_id',
    ],
],

'notifications' => [
    'channels' => [
        'webhook' => true, // Enable webhooks for multi-tenant
    ],
    'webhook_url' => 'https://tenant-{tenant_id}.your-app.com/webhook',
],
```

### High-Security Setup

```php
'security' => [
    'token_lifetime_days' => 7,       // Shorter token lifetime
    'api_key_lifetime_days' => 30,    // Shorter API key lifetime
    'token_refresh_interval' => 1800, // More frequent refresh
    
    'rate_limits' => [
        'login' => 5,            // Stricter rate limits
        'token_refresh' => 3,
        'api_request' => 50,
    ],
    
    'require_client_id' => true,
    'require_device_token' => true,
    'auto_revoke_expired_tokens' => true,
],

'notifications' => [
    'types' => [
        'suspicious_login' => true, // Enable all security notifications
        'new_device' => true,
    ],
],
```

### Performance-Optimized Setup

```php
'service' => [
    'auto_track_logins' => true,
    'log_events' => false,        // Disable logging for performance
    'cleanup_old_logs' => true,
    'cleanup_after_days' => 30,   // More aggressive cleanup
],

'notifications' => [
    'enabled' => false, // Disable notifications for performance
],
```

### Development Setup

```php
'ip_lookup' => [
    'provider' => false, // Disable IP lookup in development
],

'notifications' => [
    'enabled' => false, // Disable notifications in development
],

'service' => [
    'log_events' => true, // Enable logging in development
],
```

## 🔍 Configuration Validation

### Validate Configuration

```php
// In your application
use Alshahari\AuthTracker\Services\AuthTrackerService;

$authTracker = app(AuthTrackerService::class);
$isValid = $authTracker->validateConfiguration();
```

### Configuration Health Check

```php
// Check if all required services are configured
$health = [
    'device_service' => class_exists(DeviceService::class),
    'notification_service' => class_exists(NotificationService::class),
    'security_service' => class_exists(SecurityService::class),
    'database_connection' => config('database.default'),
    'cache_driver' => config('cache.default'),
];
```

## 📝 Environment Variables

### Required Environment Variables

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=120
```

### Optional Environment Variables

```env
# Cache
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Notifications
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls

# Webhooks
AUTH_TRACKER_WEBHOOK_URL=https://your-webhook-url.com
```

## 🚀 Configuration Best Practices

### 1. Environment-Specific Configuration

```php
// config/auth_tracker.php
'notifications' => [
    'enabled' => env('AUTH_TRACKER_NOTIFICATIONS_ENABLED', true),
    'webhook_url' => env('AUTH_TRACKER_WEBHOOK_URL'),
],

'ip_lookup' => [
    'provider' => env('AUTH_TRACKER_IP_PROVIDER', false),
    'environments' => explode(',', env('AUTH_TRACKER_IP_ENVIRONMENTS', 'production')),
],
```

### 2. Conditional Configuration

```php
'notifications' => [
    'enabled' => config('app.env') === 'production',
    'channels' => [
        'email' => config('mail.default') !== null,
        'push' => config('broadcasting.default') !== null,
    ],
],
```

### 3. Dynamic Configuration

```php
// In your service provider
public function boot()
{
    // Set configuration based on user type
    if (auth()->check()) {
        $user = auth()->user();
        if ($user->isAdmin()) {
            config(['auth_tracker.security.rate_limits.login' => 20]);
        }
    }
}
```

---

**📚 This configuration guide covers all available options in Laravel Auth Tracker.**
