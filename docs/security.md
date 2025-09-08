# Security Guide

## 🛡️ Overview

This guide covers security features and best practices for Laravel Auth Tracker.

## 🔐 Security Features

### Client ID and Device Token System

The package implements a robust security system using:

- **Client ID**: Unique identifier for each device
- **Device Token**: Secure token for API authentication
- **Token Refresh**: Automatic token refresh mechanism
- **Rate Limiting**: Protection against abuse

### Security Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Mobile App    │    │   Web Browser   │    │   API Client    │
└─────────┬───────┘    └─────────┬───────┘    └─────────┬───────┘
          │                      │                      │
          │ Client ID + Token    │ Session + Device     │ API Key
          │                      │                      │
          ▼                      ▼                      ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Security Service                             │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐            │
│  │ Validation  │  │ Rate Limit  │  │ Risk Score  │            │
│  └─────────────┘  └─────────────┘  └─────────────┘            │
└─────────────────────────────────────────────────────────────────┘
```

## 🔑 Authentication Methods

### 1. Session-Based Authentication

```php
// Web applications
Route::middleware(['web', 'auth'])->group(function () {
    // Protected routes
});
```

### 2. Token-Based Authentication

```php
// API applications
Route::middleware(['auth:sanctum', 'device.auth'])->group(function () {
    // Protected API routes
});
```

### 3. Device-Specific Authentication

```php
// Device-specific authentication
$device = $securityService->validateApiRequest($clientId, $deviceToken);
if (!$device) {
    return response()->json(['error' => 'Invalid device credentials'], 401);
}
```

## 🚨 Suspicious Activity Detection

### Detection Criteria

The system automatically detects suspicious activities based on:

1. **New Device Detection**
   - Device not previously seen for the user
   - Different device fingerprint

2. **Location Changes**
   - Login from different country/region
   - Significant geographic distance from previous login

3. **Unusual Timing**
   - Login outside normal hours
   - Login at unusual times for the user

4. **Behavioral Patterns**
   - Multiple failed login attempts
   - Rapid successive logins
   - Unusual user agent patterns

### Risk Scoring

```php
class RiskAssessmentService
{
    public function calculateRiskScore(Device $device, array $context): int
    {
        $score = 0;
        
        // New device penalty
        if ($this->isNewDevice($device)) {
            $score += 30;
        }
        
        // Location change penalty
        if ($this->isDifferentLocation($context)) {
            $score += 20;
        }
        
        // Unusual time penalty
        if ($this->isUnusualTime()) {
            $score += 15;
        }
        
        // Device reputation
        $score += $this->getDeviceReputationScore($device);
        
        return min($score, 100);
    }
}
```

## 🔒 Token Management

### Device Token Lifecycle

```php
// Token generation
$deviceToken = $securityService->generateDeviceToken($device);

// Token validation
$isValid = $securityService->validateDeviceToken($device, $token);

// Token refresh
$newToken = $securityService->refreshDeviceToken($device, $currentToken);
```

### Token Security

- **Hashing**: All tokens are hashed using Laravel's Hash facade
- **Expiration**: Tokens have configurable expiration times
- **Refresh**: Tokens can be refreshed with rate limiting
- **Revocation**: Tokens can be revoked immediately

### API Key Management

```php
// Generate API key
$apiKey = $securityService->generateApiKey($device);

// Validate API key
$device = $securityService->validateApiKey($apiKey);
```

## 🚦 Rate Limiting

### Built-in Rate Limits

```php
'security' => [
    'rate_limits' => [
        'login' => 10,           // 10 login attempts per hour
        'token_refresh' => 5,    // 5 token refreshes per hour
        'api_request' => 100,    // 100 API requests per hour
    ],
],
```

### Custom Rate Limiting

```php
// Check rate limit for specific action
$allowed = $securityService->checkRateLimit($device, 'custom_action');

if (!$allowed) {
    return response()->json(['error' => 'Rate limit exceeded'], 429);
}
```

### Rate Limit Headers

```http
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1642248000
Retry-After: 60
```

## 🔍 Security Monitoring

### Security Events

The package logs various security events:

```php
// Login events
Log::info('User login', [
    'user_id' => $user->id,
    'device_id' => $device->id,
    'ip' => $request->ip(),
    'risk_score' => $riskScore,
]);

// Suspicious activity
Log::warning('Suspicious login detected', [
    'user_id' => $user->id,
    'reasons' => $suspiciousReasons,
    'ip' => $request->ip(),
]);
```

### Security Alerts

```php
// Send security alert
$notificationService->sendSuspiciousLoginNotification($user, $device, $login);
```

## 🛡️ Data Protection

### Sensitive Data Handling

```php
// Hide sensitive data in responses
protected $hidden = [
    'device_token',
    'api_key',
    'fcm_token',
];
```

### Data Encryption

```php
// Encrypt sensitive data
$encryptedToken = encrypt($deviceToken);

// Decrypt when needed
$deviceToken = decrypt($encryptedToken);
```

### Data Anonymization

```php
// Anonymize user data for GDPR compliance
$privacyService->anonymizeUserData($user);
```

## 🔐 Security Best Practices

### 1. Secure Configuration

```php
// Production security settings
'security' => [
    'token_lifetime_days' => 7,       // Short token lifetime
    'require_client_id' => true,      // Always require client ID
    'require_device_token' => true,   // Always require device token
    'auto_revoke_expired_tokens' => true,
],
```

### 2. Input Validation

```php
// Validate device information
$request->validate([
    'x-device-udid' => 'required|string|max:100',
    'x-device-os' => 'required|string|in:android,ios,windows,macos,linux',
    'x-device-manufacturer' => 'required|string|max:100',
    'x-device-model' => 'required|string|max:100',
]);
```

### 3. HTTPS Enforcement

```php
// Force HTTPS in production
if (config('app.env') === 'production') {
    URL::forceScheme('https');
}
```

### 4. CORS Configuration

```php
// Configure CORS for API endpoints
'cors' => [
    'allowed_origins' => ['https://yourdomain.com'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Client-ID', 'X-Device-Token'],
],
```

## 🚨 Security Incident Response

### Incident Detection

```php
// Monitor for security incidents
$incidents = SecurityIncident::where('created_at', '>=', now()->subHours(24))->get();

foreach ($incidents as $incident) {
    // Send alert to security team
    $this->sendSecurityAlert($incident);
}
```

### Incident Response

```php
// Automatic response to security incidents
if ($riskScore > 80) {
    // Revoke all user sessions
    $user->logoutAll();
    
    // Send security notification
    $notificationService->sendSecurityAlert($user, $incident);
    
    // Log incident
    SecurityIncident::create([
        'user_id' => $user->id,
        'type' => 'high_risk_login',
        'details' => $incidentDetails,
    ]);
}
```

## 🔍 Security Auditing

### Audit Trail

```php
// Log all security-related actions
Log::channel('security')->info('Security action', [
    'action' => 'token_refresh',
    'user_id' => $user->id,
    'device_id' => $device->id,
    'ip' => $request->ip(),
    'timestamp' => now(),
]);
```

### Security Reports

```php
// Generate security reports
$report = $securityService->generateSecurityReport($user, '30d');

// Report includes:
// - Login patterns
// - Device usage
// - Suspicious activities
// - Security incidents
```

## 🛠️ Security Testing

### Security Test Suite

```php
class SecurityTest extends TestCase
{
    public function test_token_validation()
    {
        $device = Device::factory()->create();
        $token = $this->securityService->generateDeviceToken($device);
        
        $this->assertTrue($this->securityService->validateDeviceToken($device, $token));
    }
    
    public function test_suspicious_login_detection()
    {
        $user = User::factory()->create();
        $device = Device::factory()->create();
        
        // Simulate suspicious login
        $login = $this->authTracker->trackLogin($user, [
            'ip' => '192.168.1.100',
            'device_id' => $device->id,
        ]);
        
        $this->assertTrue($login->is_suspicious);
    }
}
```

### Penetration Testing

```php
// Test rate limiting
public function test_rate_limiting()
{
    $device = Device::factory()->create();
    
    // Make multiple requests
    for ($i = 0; $i < 10; $i++) {
        $response = $this->post('/api/login', $this->loginData);
    }
    
    // Should be rate limited
    $this->assertEquals(429, $response->status());
}
```

## 📊 Security Metrics

### Key Security Metrics

- **Login Success Rate**: Percentage of successful logins
- **Suspicious Activity Rate**: Percentage of suspicious logins
- **Token Refresh Rate**: Frequency of token refreshes
- **Device Trust Score**: Average device trust level
- **Security Incident Rate**: Number of security incidents per day

### Security Dashboard

```php
// Security metrics for dashboard
$metrics = [
    'total_logins' => Login::count(),
    'suspicious_logins' => Login::where('is_suspicious', true)->count(),
    'active_devices' => Device::active()->count(),
    'trusted_devices' => Device::trusted()->count(),
    'security_incidents' => SecurityIncident::count(),
];
```

## 🔐 Compliance

### GDPR Compliance

```php
// Data anonymization for GDPR
$privacyService->anonymizeUserData($user);

// Data export for GDPR
$userData = $privacyService->exportUserData($user);
```

### SOC 2 Compliance

```php
// Security controls for SOC 2
$controls = [
    'access_control' => $this->implementAccessControl(),
    'data_encryption' => $this->implementDataEncryption(),
    'audit_logging' => $this->implementAuditLogging(),
    'incident_response' => $this->implementIncidentResponse(),
];
```

---

**🛡️ This security guide covers all security features and best practices in Laravel Auth Tracker.**
