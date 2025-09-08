# Troubleshooting Guide

## 🔍 Common Issues

### Installation Issues

#### 1. Migration Errors

**Problem**: Migration fails with database errors

**Solution**:
```bash
# Check database connection
php artisan migrate:status

# Reset migrations
php artisan migrate:reset
php artisan migrate

# Check database permissions
mysql -u username -p -e "SHOW GRANTS FOR 'username'@'localhost';"
```

#### 2. Service Provider Not Found

**Problem**: `Class 'Alshahari\AuthTracker\AuthTrackerServiceProvider' not found`

**Solution**:
```bash
# Clear composer cache
composer clear-cache

# Reinstall package
composer remove abobakeralshahari/laravel-auth-tracker
composer require abobakeralshahari/laravel-auth-tracker

# Clear application cache
php artisan config:clear
php artisan cache:clear
```

#### 3. Configuration Not Published

**Problem**: Configuration file not found

**Solution**:
```bash
# Publish configuration
php artisan vendor:publish --provider="Alshahari\AuthTracker\AuthTrackerServiceProvider" --tag="config"

# Check if file exists
ls -la config/auth_tracker.php
```

### Authentication Issues

#### 1. User Provider Not Working

**Problem**: Users not being tracked

**Solution**:
```php
// Check config/auth.php
'providers' => [
    'users' => [
        'driver' => 'eloquent-tracked', // Must be 'eloquent-tracked'
        'model' => App\User::class,
    ],
],

// Check if trait is added
class User extends Authenticatable
{
    use AuthTracking; // Must be present
}
```

#### 2. Login Not Being Tracked

**Problem**: Logins not appearing in database

**Solution**:
```php
// Check if auto-tracking is enabled
'service' => [
    'auto_track_logins' => true, // Must be true
],

// Check event listeners
Event::listen('Illuminate\Auth\Events\Login', function ($event) {
    // Should be triggered on login
});
```

#### 3. Device Not Being Registered

**Problem**: Device not being created

**Solution**:
```php
// Check middleware registration
protected $middlewareGroups = [
    'web' => [
        \Alshahari\AuthTracker\Middleware\StoreDevice::class,
    ],
];

// Check required headers
$request->header('x-device-udid'); // Must be present
$request->header('x-device-os');   // Must be present
```

### Configuration Issues

#### 1. Parser Not Working

**Problem**: User agent parsing fails

**Solution**:
```bash
# Install required parser
composer require whichbrowser/parser
# or
composer require jenssegers/agent

# Check configuration
'parser' => 'whichbrowser', // or 'agent'
```

#### 2. IP Lookup Not Working

**Problem**: IP geolocation not working

**Solution**:
```php
// Check IP lookup configuration
'ip_lookup' => [
    'provider' => 'ip-api', // or 'ip2location-lite'
    'timeout' => 5.0,
    'environments' => ['production'],
],

// Check if in correct environment
if (in_array(config('app.env'), config('auth_tracker.ip_lookup.environments'))) {
    // IP lookup will work
}
```

#### 3. Notifications Not Sending

**Problem**: Notifications not being sent

**Solution**:
```php
// Check notification configuration
'notifications' => [
    'enabled' => true,
    'channels' => [
        'email' => true,    // Check email configuration
        'push' => true,     // Check push configuration
        'database' => true, // Check database notifications
    ],
],

// Check email configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-password
```

### Database Issues

#### 1. Table Not Found

**Problem**: `Table 'logins' doesn't exist`

**Solution**:
```bash
# Run migrations
php artisan migrate

# Check migration status
php artisan migrate:status

# Check if table exists
php artisan tinker
>>> Schema::hasTable('logins')
```

#### 2. Foreign Key Constraints

**Problem**: Foreign key constraint violations

**Solution**:
```sql
-- Check foreign key constraints
SHOW CREATE TABLE logins;

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;
-- Your operations
SET FOREIGN_KEY_CHECKS = 1;
```

#### 3. Index Issues

**Problem**: Slow queries

**Solution**:
```sql
-- Add missing indexes
ALTER TABLE logins ADD INDEX idx_authenticatable (authenticatable_type, authenticatable_id);
ALTER TABLE logins ADD INDEX idx_device (device_id);
ALTER TABLE logins ADD INDEX idx_logout (logout_at, cleared_by_user);
ALTER TABLE logins ADD INDEX idx_created (created_at);

-- Check query performance
EXPLAIN SELECT * FROM logins WHERE authenticatable_id = 1;
```

### Performance Issues

#### 1. Slow Login Tracking

**Problem**: Login tracking is slow

**Solution**:
```php
// Use database transactions
DB::transaction(function () use ($user, $context) {
    $login = $this->authTracker->trackLogin($user, $context);
});

// Use queued jobs for heavy operations
dispatch(new TrackLoginJob($user, $context));
```

#### 2. Memory Issues

**Problem**: High memory usage

**Solution**:
```php
// Limit query results
$logins = $user->logins()
    ->select(['id', 'created_at', 'ip', 'device_type'])
    ->limit(100)
    ->get();

// Use pagination
$logins = $user->logins()->paginate(15);
```

#### 3. Cache Issues

**Problem**: Cache not working properly

**Solution**:
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Check cache driver
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Security Issues

#### 1. Token Validation Failing

**Problem**: Device tokens not validating

**Solution**:
```php
// Check token generation
$token = $securityService->generateDeviceToken($device);

// Check token validation
$isValid = $securityService->validateDeviceToken($device, $token);

// Check token expiration
if ($device->token_expires_at && $device->token_expires_at->isPast()) {
    // Token expired
}
```

#### 2. Rate Limiting Issues

**Problem**: Rate limiting not working

**Solution**:
```php
// Check rate limit configuration
'rate_limits' => [
    'login' => 10,
    'token_refresh' => 5,
    'api_request' => 100,
],

// Check cache driver
CACHE_DRIVER=redis // Must be redis or memcached
```

#### 3. Suspicious Activity Detection

**Problem**: Suspicious activities not detected

**Solution**:
```php
// Check suspicious activity configuration
'notifications' => [
    'types' => [
        'suspicious_login' => true, // Must be true
    ],
],

// Check detection criteria
$isNewDevice = !$user->devices()->where('id', $device->id)->exists();
$isDifferentLocation = $lastLogin && $lastLogin->country !== $login->country;
```

## 🔧 Debugging Tools

### 1. Enable Debug Mode

```php
// In config/app.php
'debug' => true,

// In config/auth_tracker.php
'service' => [
    'log_events' => true, // Enable event logging
],
```

### 2. Log Analysis

```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check specific log channel
tail -f storage/logs/security.log
```

### 3. Database Queries

```php
// Enable query logging
DB::enableQueryLog();

// Your operations
$logins = $user->logins()->get();

// Check queries
$queries = DB::getQueryLog();
dd($queries);
```

### 4. Service Testing

```php
// Test services individually
$deviceService = app(DeviceService::class);
$device = $deviceService->getOrCreateDevice($context);

$notificationService = app(NotificationService::class);
$notificationService->sendLoginNotification($user, $device, $login);

$securityService = app(SecurityService::class);
$isValid = $securityService->validateDeviceToken($device, $token);
```

## 🚨 Error Codes

### Common Error Codes

| Code | Description | Solution |
|------|-------------|----------|
| `AUTH_TRACKER_001` | Device not found | Check device registration |
| `AUTH_TRACKER_002` | Invalid token | Check token generation/validation |
| `AUTH_TRACKER_003` | Rate limit exceeded | Wait and retry |
| `AUTH_TRACKER_004` | Configuration error | Check configuration file |
| `AUTH_TRACKER_005` | Database error | Check database connection |

### Error Handling

```php
try {
    $login = $this->authTracker->trackLogin($user, $context);
} catch (AuthTrackerException $e) {
    Log::error('Auth Tracker Error', [
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
        'context' => $context,
    ]);
    
    // Handle specific errors
    switch ($e->getCode()) {
        case 'AUTH_TRACKER_001':
            // Device not found
            break;
        case 'AUTH_TRACKER_002':
            // Invalid token
            break;
    }
}
```

## 📊 Performance Monitoring

### 1. Query Performance

```sql
-- Check slow queries
SHOW VARIABLES LIKE 'slow_query_log';
SHOW VARIABLES LIKE 'long_query_time';

-- Analyze query performance
EXPLAIN SELECT * FROM logins WHERE authenticatable_id = 1;
```

### 2. Memory Usage

```php
// Check memory usage
$memoryUsage = memory_get_usage(true);
$peakMemory = memory_get_peak_usage(true);

Log::info('Memory Usage', [
    'current' => $memoryUsage,
    'peak' => $peakMemory,
]);
```

### 3. Response Times

```php
// Measure response times
$startTime = microtime(true);

// Your operations
$login = $this->authTracker->trackLogin($user, $context);

$endTime = microtime(true);
$executionTime = $endTime - $startTime;

Log::info('Execution Time', [
    'time' => $executionTime,
    'operation' => 'track_login',
]);
```

## 🔍 Health Checks

### 1. Service Health Check

```php
// Check if all services are working
$health = [
    'device_service' => class_exists(DeviceService::class),
    'notification_service' => class_exists(NotificationService::class),
    'security_service' => class_exists(SecurityService::class),
    'database_connection' => DB::connection()->getPdo() !== null,
    'cache_driver' => Cache::store()->getStore() !== null,
];
```

### 2. Configuration Health Check

```php
// Check configuration
$configHealth = [
    'table_name' => config('auth_tracker.table_name'),
    'device_model' => config('auth_tracker.device_model'),
    'parser' => config('auth_tracker.parser'),
    'notifications_enabled' => config('auth_tracker.notifications.enabled'),
];
```

### 3. Database Health Check

```php
// Check database tables
$tables = ['logins', 'devices'];
foreach ($tables as $table) {
    if (!Schema::hasTable($table)) {
        Log::error("Table {$table} not found");
    }
}
```

## 📞 Support

### Getting Help

1. **Check Logs**: Always check Laravel logs first
2. **Enable Debug**: Set `APP_DEBUG=true` in `.env`
3. **Check Configuration**: Verify all configuration settings
4. **Test Services**: Test each service individually
5. **Check Dependencies**: Ensure all required packages are installed

### Reporting Issues

When reporting issues, include:

- Laravel version
- PHP version
- Package version
- Error messages
- Log files
- Configuration (without sensitive data)
- Steps to reproduce

---

**🔍 This troubleshooting guide covers common issues and solutions for Laravel Auth Tracker.**
