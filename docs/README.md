# Laravel Auth Tracker Documentation

Welcome to the comprehensive documentation for Laravel Auth Tracker. This documentation covers all aspects of the package, from installation to advanced usage.

## 📚 Documentation Index

### Getting Started
- [Installation Guide](installation.md) - Complete step-by-step installation instructions
- [Configuration Guide](configuration.md) - Detailed configuration options and examples

### API Reference
- [API Reference](api-reference.md) - Complete API documentation with examples

### Security
- [Security Guide](security.md) - Security features, best practices, and compliance

### Troubleshooting
- [Troubleshooting Guide](troubleshooting.md) - Common issues and solutions

## 🚀 Quick Start

### 1. Installation

```bash
composer require abobakeralshahari/laravel-auth-tracker
php artisan vendor:publish --provider="Alshahari\AuthTracker\AuthTrackerServiceProvider" --tag="config"
php artisan migrate
```

### 2. Basic Setup

Add the trait to your User model:

```php
use Alshahari\AuthTracker\Traits\AuthTracking;

class User extends Authenticatable
{
    use AuthTracking;
}
```

### 3. Service Usage

```php
use Alshahari\AuthTracker\Services\AuthTrackerService;

class YourController extends Controller
{
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
        ]);
    }
}
```

## 🔧 Key Features

### Service-Based Architecture
- No direct endpoints - pure service layer
- Clean, testable, and maintainable code
- Easy integration with existing applications

### Advanced Device Management
- Dynamic device attribute configuration
- Client ID and device token system
- Device trust levels and security

### Smart Notifications
- Multiple notification channels
- Configurable notification types
- Suspicious activity detection

### Security Features
- Token refresh mechanism
- Rate limiting
- Risk assessment
- Comprehensive logging

## 📖 Documentation Structure

### Installation & Setup
- [Installation Guide](installation.md) - Complete installation process
- [Configuration Guide](configuration.md) - All configuration options

### API & Usage
- [API Reference](api-reference.md) - Complete API documentation
- [Security Guide](security.md) - Security features and best practices

### Support & Maintenance
- [Troubleshooting Guide](troubleshooting.md) - Common issues and solutions

## 🎯 Use Cases

### Web Applications
- Track user sessions across devices
- Monitor login patterns
- Detect suspicious activities

### Mobile Applications
- Device registration and management
- Token-based authentication
- Push notification integration

### API Services
- Client authentication
- Rate limiting
- Security monitoring

## 🔍 Quick Reference

### Services
- `AuthTrackerService` - Main service for tracking logins
- `DeviceService` - Device management and registration
- `NotificationService` - Notification handling
- `SecurityService` - Security and token management

### Models
- `Login` - Login tracking model
- `Device` - Device information model

### Events
- `LoginDetected` - Fired when login is detected
- `SuspiciousLoginDetected` - Fired for suspicious activities
- `DeviceRegistered` - Fired when device is registered

## 🛠️ Development

### Testing
```bash
php artisan test
php artisan test --filter=AuthTrackerTest
```

### Debugging
```php
// Enable debug mode
config(['auth_tracker.service.log_events' => true]);

// Check logs
tail -f storage/logs/laravel.log
```

### Performance
```php
// Use database transactions
DB::transaction(function () use ($user, $context) {
    $login = $this->authTracker->trackLogin($user, $context);
});

// Use queued jobs for heavy operations
dispatch(new TrackLoginJob($user, $context));
```

## 📞 Support

### Getting Help
1. Check the [Troubleshooting Guide](troubleshooting.md)
2. Review the [Configuration Guide](configuration.md)
3. Check Laravel logs for errors
4. Enable debug mode for detailed information

### Reporting Issues
When reporting issues, please include:
- Laravel version
- PHP version
- Package version
- Error messages
- Log files
- Steps to reproduce

## 📝 Contributing

We welcome contributions! Please see our [Contributing Guide](../CONTRIBUTING.md) for details.

## 📄 License

This project is licensed under the [MIT License](../LICENSE).

---

**📚 This documentation covers all aspects of Laravel Auth Tracker. Start with the [Installation Guide](installation.md) to get up and running quickly!**
