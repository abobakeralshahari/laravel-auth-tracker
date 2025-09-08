# Detailed Installation Guide

## 📋 Requirements

### System Requirements
- **PHP**: 7.2.5 or higher
- **Laravel**: 5.8 or higher
- **Database**: MySQL 5.7+, PostgreSQL 9.6+, SQLite 3.8.8+
- **Composer**: 2.0 or higher

### Required PHP Extensions
```bash
php -m | grep -E "(openssl|pdo|mbstring|tokenizer|xml|ctype|json|bcmath)"
```

### Optional PHP Extensions
- **Redis**: for performance improvement
- **Memcached**: for caching
- **Guzzle**: for IP lookup

## 🚀 Step-by-Step Installation

### Step 1: Install Package

```bash
# Install via Composer
composer require abobakeralshahari/laravel-auth-tracker

# Or add manually to composer.json
{
    "require": {
        "abobakeralshahari/laravel-auth-tracker": "^2.0"
    }
}
```

### Step 2: Publish Configuration Files

```bash
# Publish configuration file
php artisan vendor:publish --provider="Alshahari\AuthTracker\AuthTrackerServiceProvider" --tag="config"

# Publish migrations
php artisan vendor:publish --provider="Alshahari\AuthTracker\AuthTrackerServiceProvider" --tag="migrations"

# Publish views (optional)
php artisan vendor:publish --provider="Alshahari\AuthTracker\AuthTrackerServiceProvider" --tag="views"
```

### Step 3: Run Migrations

```bash
# Run migrations
php artisan migrate

# Or with rollback for testing
php artisan migrate --force
```

### Step 4: Environment Configuration

#### In `.env` file
```env
# Database settings
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Session settings
SESSION_DRIVER=database
SESSION_LIFETIME=120

# Cache settings (optional)
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## ⚙️ Advanced Configuration

### 1. User Provider Configuration

In `config/auth.php`:

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent-tracked',
        'model' => App\User::class,
    ],
    
    // For multiple user types
    'admins' => [
        'driver' => 'eloquent-tracked',
        'model' => App\Admin::class,
    ],
],
```

### 2. Package Configuration

In `config/auth_tracker.php`:

```php
return [
    // Table name
    'table_name' => 'logins',
    
    // Remember token lifetime
    'remember_lifetime' => 365, // days
    
    // Device model
    'device_model' => Alshahari\AuthTracker\Models\Device::class,
    
    // User Agent parser
    'parser' => 'whichbrowser', // or 'agent'
    
    // IP Lookup configuration
    'ip_lookup' => [
        'provider' => 'ip-api', // or 'ip2location-lite'
        'timeout' => 5.0,
        'environments' => ['production'],
        'custom_providers' => [
            'my-provider' => App\Providers\MyIpProvider::class,
        ],
    ],
];
```

### 3. Middleware Configuration

In `app/Http/Kernel.php`:

```php
protected $middlewareGroups = [
    'web' => [
        // ... other middleware
        \Alshahari\AuthTracker\Middleware\StoreDevice::class,
    ],
    
    'api' => [
        // ... other middleware
        \Alshahari\AuthTracker\Middleware\StoreDevice::class,
    ],
];

protected $routeMiddleware = [
    // ... other middleware
    'store.device' => \Alshahari\AuthTracker\Middleware\StoreDevice::class,
];
```

## 🔧 Model Setup

### 1. Update User Model

```php
<?php

namespace App;

use Alshahari\AuthTracker\Traits\AuthTracking;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens; // if using Passport
use Laravel\Sanctum\HasApiTokens as SanctumTokens; // if using Sanctum

class User extends Authenticatable
{
    use AuthTracking, HasApiTokens; // or SanctumTokens
    
    protected $fillable = [
        'name', 'email', 'password',
    ];
    
    protected $hidden = [
        'password', 'remember_token',
    ];
    
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
```

### 2. Update LoginController

```php
<?php

namespace App\Http\Controllers\Auth;

use Alshahari\AuthTracker\Traits\AuthenticatesWithTracking;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    use AuthenticatesWithTracking;
    
    protected $redirectTo = '/dashboard';
    
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    
    // You can add additional methods here
}
```

## 📱 Mobile App Setup

### 1. Headers Configuration

```php
// In Flutter/Dart app
final headers = {
  'Content-Type': 'application/json',
  'x-device-udid': deviceId,
  'x-device-os': Platform.isAndroid ? 'android' : 'ios',
  'x-device-os-version': Platform.operatingSystemVersion,
  'x-device-manufacturer': await DeviceInfoPlugin().androidInfo.then((info) => info.manufacturer),
  'x-device-model': await DeviceInfoPlugin().androidInfo.then((info) => info.model),
  'x-device-app-version': packageInfo.version,
  'x-device-fcm-token': fcmToken,
};
```

### 2. API Routes Setup

```php
// In routes/api.php
Route::middleware(['auth:sanctum', 'store.device'])->group(function () {
    Route::get('/user/logins', [AuthTrackerController::class, 'getLogins']);
    Route::post('/user/logout/{id}', [AuthTrackerController::class, 'logoutById']);
    Route::post('/user/logout-others', [AuthTrackerController::class, 'logoutOthers']);
});
```

## 🌍 IP Lookup Setup

### 1. Using IP-API

```bash
# No need to install additional packages
# Just update configuration
```

```php
// In config/auth_tracker.php
'ip_lookup' => [
    'provider' => 'ip-api',
    'timeout' => 5.0,
    'environments' => ['production'],
],
```

### 2. Using Ip2Location

```bash
# Download database
# 1. Go to https://lite.ip2location.com/
# 2. Download database
# 3. Import to database

# Add tables
CREATE TABLE `ip2location_db3` (
    `ip_from` INT(10) UNSIGNED,
    `ip_to` INT(10) UNSIGNED,
    `country_code` CHAR(2),
    `country_name` VARCHAR(64),
    `region_name` VARCHAR(128),
    `city_name` VARCHAR(128),
    `latitude` DECIMAL(10, 8),
    `longitude` DECIMAL(11, 8),
    `zip_code` VARCHAR(10),
    `time_zone` VARCHAR(8),
    INDEX `idx_ip_from` (`ip_from`),
    INDEX `idx_ip_to` (`ip_to`)
);
```

## 🧪 Testing

### 1. Installation Testing

```bash
# Run tests
php artisan test

# Run specific tests
php artisan test --filter=AuthTrackerTest
```

### 2. Manual Testing

```php
// In tinker
php artisan tinker

// Test login
$user = User::first();
auth()->login($user);

// Check session
auth()->user()->logins;

// Test logout
auth()->user()->logout();
```

## 🔍 Troubleshooting

### Common Issues

#### 1. Migration Error
```bash
# Solution: Re-run migrations
php artisan migrate:rollback
php artisan migrate
```

#### 2. User Provider Error
```php
// Check configuration in config/auth.php
'providers' => [
    'users' => [
        'driver' => 'eloquent-tracked', // Important!
        'model' => App\User::class,
    ],
],
```

#### 3. Middleware Error
```php
// Check middleware registration
protected $middlewareGroups = [
    'web' => [
        \Alshahari\AuthTracker\Middleware\StoreDevice::class,
    ],
];
```

#### 4. Parser Error
```bash
# Install required parser
composer require whichbrowser/parser
# or
composer require jenssegers/agent
```

## 📊 Performance Monitoring

### 1. Add Indexes

```sql
-- Add indexes for better performance
ALTER TABLE logins ADD INDEX idx_authenticatable (authenticatable_type, authenticatable_id);
ALTER TABLE logins ADD INDEX idx_device (device_id);
ALTER TABLE logins ADD INDEX idx_logout (logout_at, cleared_by_user);
ALTER TABLE logins ADD INDEX idx_created (created_at);
```

### 2. Clean Old Data

```php
// Add command to clean old data
php artisan make:command CleanOldLogins

// In Command
public function handle()
{
    Login::where('created_at', '<', now()->subMonths(6))
         ->where('logout_at', '!=', null)
         ->delete();
}
```

## 🎯 Next Steps

After successful installation:

1. **Test Installation** using the test suite
2. **Review Configuration** in `config/auth_tracker.php`
3. **Add Middleware** to required routes
4. **Test Login** from different devices
5. **Review Data** in the database

---

**🎉 Congratulations! Laravel Auth Tracker has been successfully installed!**