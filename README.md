# Laravel Auth Tracker

[![Latest Version](https://img.shields.io/badge/version-2.0.0-blue.svg)](https://github.com/abobakeralshahari/laravel-auth-tracker)
[![Laravel Version](https://img.shields.io/badge/Laravel-5.8%2B-red.svg)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-7.2%2B-purple.svg)](https://php.net)

## 📋 Overview

**Laravel Auth Tracker** is an advanced service-based package for tracking and managing user authentication sessions in Laravel applications. It provides a comprehensive system for tracking logins, managing devices, and controlling sessions with full support for Laravel Passport and Sanctum.

## ✨ Key Features

### 🔐 Comprehensive Session Tracking
- Track web sessions (Sessions)
- Laravel Passport API support
- Laravel Sanctum API support
- Detailed login tracking

### 📱 Advanced Device Management
- Link devices to users
- Track device details (OS, model, etc.)
- Support for mobile and web applications
- FCM token management for notifications
- Dynamic device attribute configuration

### 🌍 Geolocation Tracking
- Extract location from IP address
- Support for multiple location service providers
- Custom provider support

### 🛡️ Advanced Security
- Client ID and device token system
- Token refresh mechanism
- Rate limiting for API protection
- Suspicious activity detection
- Protection against session fixation attacks

### 🔔 Smart Notifications
- New device login notifications
- Suspicious activity alerts
- Multiple notification channels (Email, Push, Database, Webhook)
- Configurable notification types

### 📊 Comprehensive Analytics
- Active session management
- Login history tracking
- Detailed statistics
- Device analytics

## 🚀 Installation

### Requirements
- PHP 7.2 or higher
- Laravel 5.8 or higher
- MySQL/PostgreSQL/SQLite

### Install via Composer

```bash
composer require abobakeralshahari/laravel-auth-tracker
```

### Publish Configuration

```bash
php artisan vendor:publish --provider="Alshahari\AuthTracker\AuthTrackerServiceProvider" --tag="config"
```

### Run Migrations

```bash
php artisan migrate
```

## ⚙️ Configuration

### 1. Setup Models

Add the `AuthTracking` trait to your user model:

```php
<?php

namespace App;

use Alshahari\AuthTracker\Traits\AuthTracking;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use AuthTracking;
    
    // Your model code...
}
```

### 2. Update LoginController

Replace `AuthenticatesUsers` with `AuthenticatesWithTracking`:

```php
<?php

namespace App\Http\Controllers\Auth;

use Alshahari\AuthTracker\Traits\AuthenticatesWithTracking;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesWithTracking;
    
    // Your controller code...
}
```

### 3. Configure User Provider

In `config/auth.php`:

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent-tracked',
        'model' => App\User::class,
    ],
],
```

### 4. Install User Agent Parser

Choose one of the following:

#### Option A: WhichBrowser
```bash
composer require whichbrowser/parser
```

#### Option B: Agent
```bash
composer require jenssegers/agent
```

Then update the configuration in `config/auth_tracker.php`:

```php
'parser' => 'whichbrowser', // or 'agent'
```

## 📖 Usage

### Service-Based Architecture

The package is designed as a service layer without direct endpoints. Use the `AuthTrackerService` in your application:

```php
use Alshahari\AuthTracker\Services\AuthTrackerService;

class YourController extends Controller
{
    protected $authTracker;

    public function __construct(AuthTrackerService $authTracker)
    {
        $this->authTracker = $authTracker;
    }

    public function login(Request $request)
    {
        // Your login logic...
        
        // Track the login
        $login = $this->authTracker->trackLogin(auth()->user(), [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'login_by' => 'web',
            'login_from' => 'web_pc',
        ]);
    }
}
```

### Device Management

```php
use Alshahari\AuthTracker\Services\DeviceService;

class DeviceController extends Controller
{
    protected $deviceService;

    public function __construct(DeviceService $deviceService)
    {
        $this->deviceService = $deviceService;
    }

    public function registerDevice(Request $request)
    {
        $device = $this->deviceService->getOrCreateDevice([
            'os' => $request->header('x-device-os'),
            'manufacturer' => $request->header('x-device-manufacturer'),
            'model' => $request->header('x-device-model'),
            'app_version' => $request->header('x-device-app-version'),
        ]);

        return response()->json([
            'client_id' => $device->client_id,
            'device_token' => $this->deviceService->generateDeviceToken($device),
        ]);
    }
}
```

### Security Features

```php
use Alshahari\AuthTracker\Services\SecurityService;

class SecurityController extends Controller
{
    protected $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    public function validateApiRequest(Request $request)
    {
        $clientId = $request->header('X-Client-ID');
        $deviceToken = $request->header('X-Device-Token');

        $device = $this->securityService->validateApiRequest($clientId, $deviceToken);
        
        if (!$device) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        // Continue with your API logic...
    }

    public function refreshToken(Request $request)
    {
        $clientId = $request->header('X-Client-ID');
        $currentToken = $request->header('X-Device-Token');

        $device = $this->securityService->validateClientId($clientId);
        
        if (!$device) {
            return response()->json(['error' => 'Invalid client ID'], 401);
        }

        $newToken = $this->securityService->refreshDeviceToken($device, $currentToken);
        
        if (!$newToken) {
            return response()->json(['error' => 'Token refresh failed'], 400);
        }

        return response()->json(['device_token' => $newToken]);
    }
}
```

### Notification System

```php
use Alshahari\AuthTracker\Services\NotificationService;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function sendLoginNotification($user, $device, $login)
    {
        $this->notificationService->sendLoginNotification($user, $device, $login);
    }
}
```

## 🔧 Advanced Configuration

### Device Configuration

```php
// config/auth_tracker.php
'device' => [
    'required_attributes' => [
        'udid',
        'os',
        'manufacturer',
        'model'
    ],
    
    'optional_attributes' => [
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
    ],
    
    'valid_os' => [
        'android',
        'ios',
        'windows',
        'macos',
        'linux',
        'web'
    ],
    
    'auto_detect' => true,
    'trust_new_devices' => false,
],
```

### Security Configuration

```php
'security' => [
    'token_lifetime_days' => 30,
    'api_key_lifetime_days' => 90,
    'token_refresh_interval' => 3600, // 1 hour in seconds
    
    'rate_limits' => [
        'login' => 10, // 10 login attempts per hour
        'token_refresh' => 5, // 5 token refreshes per hour
        'api_request' => 100, // 100 API requests per hour
    ],
    
    'require_client_id' => true,
    'require_device_token' => true,
    'auto_revoke_expired_tokens' => true,
],
```

### Notification Configuration

```php
'notifications' => [
    'enabled' => true,
    
    'types' => [
        'login' => true,
        'new_device' => true,
        'suspicious_login' => true,
        'device_registration' => true,
        'general_login' => false,
    ],
    
    'channels' => [
        'email' => true,
        'push' => true,
        'database' => true,
        'webhook' => false,
    ],
    
    'email_classes' => [
        'new_device' => \App\Notifications\NewDeviceLogin::class,
        'suspicious_login' => \App\Notifications\SuspiciousLogin::class,
        'device_registration' => \App\Notifications\DeviceRegistered::class,
    ],
],
```

## 📱 Mobile App Integration

### Flutter Example

```dart
class AuthTrackerService {
  static const String baseUrl = 'https://api.example.com';
  
  Future<Map<String, dynamic>> registerDevice() async {
    final response = await http.post(
      Uri.parse('$baseUrl/api/devices/register'),
      headers: {
        'Content-Type': 'application/json',
        'x-device-os': Platform.isAndroid ? 'android' : 'ios',
        'x-device-manufacturer': await DeviceInfoPlugin().androidInfo.then((info) => info.manufacturer),
        'x-device-model': await DeviceInfoPlugin().androidInfo.then((info) => info.model),
        'x-device-app-version': packageInfo.version,
      },
    );
    
    return json.decode(response.body);
  }
  
  Future<Map<String, dynamic>> refreshToken(String clientId, String currentToken) async {
    final response = await http.post(
      Uri.parse('$baseUrl/api/security/refresh-token'),
      headers: {
        'Content-Type': 'application/json',
        'X-Client-ID': clientId,
        'X-Device-Token': currentToken,
      },
    );
    
    return json.decode(response.body);
  }
}
```

### React Native Example

```javascript
import axios from 'axios';

const authTrackerAPI = axios.create({
  baseURL: 'https://api.example.com',
  headers: {
    'Content-Type': 'application/json',
  },
});

export const registerDevice = (deviceInfo) => {
  return authTrackerAPI.post('/api/devices/register', deviceInfo);
};

export const refreshToken = (clientId, currentToken) => {
  return authTrackerAPI.post('/api/security/refresh-token', {
    client_id: clientId,
    device_token: currentToken,
  });
};
```

## 🔒 Security Features

### Client ID and Device Token System

1. **Client ID**: Unique identifier for each device
2. **Device Token**: Secure token for API authentication
3. **Token Refresh**: Automatic token refresh mechanism
4. **Rate Limiting**: Protection against abuse

### Suspicious Activity Detection

- New device detection
- Different location login
- Unusual time login
- Different IP pattern

### API Protection

```php
// Middleware for API protection
Route::middleware(['auth:sanctum', 'device.auth'])->group(function () {
    // Protected API routes
});
```

## 📊 Analytics and Statistics

```php
use Alshahari\AuthTracker\Services\AuthTrackerService;

class AnalyticsController extends Controller
{
    public function getStatistics(AuthTrackerService $authTracker)
    {
        $user = auth()->user();
        
        $statistics = $authTracker->getLoginStatistics($user, [
            'date_from' => now()->subDays(30),
            'date_to' => now(),
        ]);
        
        return response()->json($statistics);
    }
}
```

## 🧪 Testing

```bash
# Run tests
php artisan test

# Run specific tests
php artisan test --filter=AuthTrackerTest
```

## 🔍 Troubleshooting

### Common Issues

1. **Device not being tracked**
   - Ensure `AuthTracking` trait is added to user model
   - Check user provider configuration

2. **Token validation failing**
   - Verify client ID and device token
   - Check token expiration

3. **Notifications not sending**
   - Check notification configuration
   - Verify notification classes exist

## 🤝 Contributing

We welcome contributions! Please read our [Contributing Guide](CONTRIBUTING.md) before submitting pull requests.

## 📝 License

This project is licensed under the [MIT License](LICENSE).

## 👨‍💻 Author

**Abobaker Al-shahari**
- Email: abobaker.m2017@gmail.com
- GitHub: [@abobakeralshahari](https://github.com/abobakeralshahari)

## 🙏 Acknowledgments

- Laravel team
- PHP community
- All contributors

## 📞 Support

If you encounter any issues or have suggestions:

1. Open a [GitHub Issue](https://github.com/abobakeralshahari/laravel-auth-tracker/issues)
2. Contact us via email
3. Join GitHub discussions

---

**⭐ If you like this project, please give it a star!**