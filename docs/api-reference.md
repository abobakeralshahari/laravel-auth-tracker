# API Reference

## 📚 Overview

This guide covers all available APIs in Laravel Auth Tracker.

## 🔐 Authentication APIs

### User Login

#### POST `/login`
User login with session tracking.

**Headers:**
```http
Content-Type: application/json
x-device-udid: unique-device-id
x-device-os: android|ios|windows|macos|linux
x-device-os-version: 10.0
x-device-manufacturer: Samsung
x-device-model: Galaxy S20
x-device-app-version: 1.0.0
x-device-fcm-token: fcm-token-here
```

**Request Body:**
```json
{
    "email": "user@example.com",
    "password": "password123",
    "remember": true,
    "login_by": "mobile_app",
    "login_from": "android_app"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com"
    },
    "login": {
        "id": 123,
        "device_type": "mobile",
        "platform": "Android",
        "browser": "Chrome Mobile",
        "ip": "192.168.1.1",
        "location": "New York, NY, USA",
        "created_at": "2024-01-15T10:30:00Z"
    }
}
```

### User Logout

#### POST `/logout`
User logout from current session.

**Headers:**
```http
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "Logout successful"
}
```

## 📱 Device Management APIs

### Get Device List

#### GET `/api/devices`
Get all user devices.

**Headers:**
```http
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "devices": [
        {
            "id": 1,
            "udid": "device-123",
            "os": "android",
            "os_version": "10.0",
            "manufacturer": "Samsung",
            "model": "Galaxy S20",
            "app_version": "1.0.0",
            "last_login": "2024-01-15T10:30:00Z",
            "is_current": true
        }
    ]
}
```

### Update Device Information

#### PUT `/api/devices/{id}`
Update specific device information.

**Headers:**
```http
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "fcm_token": "new-fcm-token",
    "app_version": "1.1.0"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Device information updated",
    "device": {
        "id": 1,
        "fcm_token": "new-fcm-token",
        "app_version": "1.1.0",
        "updated_at": "2024-01-15T11:00:00Z"
    }
}
```

## 🔍 Login Tracking APIs

### Get Active Sessions

#### GET `/api/logins/active`
Get all active user sessions.

**Headers:**
```http
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (optional): Page number
- `per_page` (optional): Items per page (default: 15)

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 123,
            "device": {
                "id": 1,
                "os": "android",
                "manufacturer": "Samsung",
                "model": "Galaxy S20"
            },
            "ip": "192.168.1.1",
            "location": "New York, NY, USA",
            "login_by": "mobile_app",
            "login_from": "android_app",
            "created_at": "2024-01-15T10:30:00Z",
            "is_current": true
        }
    ],
    "pagination": {
        "current_page": 1,
        "per_page": 15,
        "total": 1,
        "last_page": 1
    }
}
```

### Get Login History

#### GET `/api/logins/history`
Get ended session history.

**Headers:**
```http
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (optional): Page number
- `per_page` (optional): Items per page
- `from_date` (optional): Start date (Y-m-d)
- `to_date` (optional): End date (Y-m-d)

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 122,
            "device": {
                "id": 2,
                "os": "ios",
                "manufacturer": "Apple",
                "model": "iPhone 12"
            },
            "ip": "192.168.1.2",
            "location": "Los Angeles, CA, USA",
            "login_by": "web",
            "login_from": "web_pc",
            "created_at": "2024-01-14T15:20:00Z",
            "logout_at": "2024-01-14T18:30:00Z",
            "duration": "3h 10m"
        }
    ],
    "pagination": {
        "current_page": 1,
        "per_page": 15,
        "total": 5,
        "last_page": 1
    }
}
```

### Revoke Specific Session

#### POST `/api/logins/{id}/logout`
Revoke specific session.

**Headers:**
```http
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "Session revoked successfully"
}
```

### Revoke All Sessions Except Current

#### POST `/api/logins/logout-others`
Revoke all sessions except current one.

**Headers:**
```http
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "All other sessions revoked",
    "logged_out_count": 3
}
```

### Revoke All Sessions

#### POST `/api/logins/logout-all`
Revoke all sessions including current.

**Headers:**
```http
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "All sessions revoked",
    "logged_out_count": 4
}
```

## 📊 Statistics APIs

### Session Statistics

#### GET `/api/logins/statistics`
Get detailed session statistics.

**Headers:**
```http
Authorization: Bearer {token}
```

**Query Parameters:**
- `period` (optional): Time period (7d, 30d, 90d, 1y)
- `group_by` (optional): Data grouping (day, week, month)

**Response:**
```json
{
    "success": true,
    "statistics": {
        "total_logins": 25,
        "active_sessions": 3,
        "unique_devices": 5,
        "login_by": {
            "mobile_app": 15,
            "web": 8,
            "api": 2
        },
        "login_from": {
            "android_app": 12,
            "ios_app": 3,
            "web_pc": 6,
            "web_mobile": 2,
            "web_tablet": 2
        },
        "devices": {
            "android": 8,
            "ios": 3,
            "windows": 2,
            "macos": 1
        },
        "locations": [
            {
                "city": "New York",
                "count": 15
            },
            {
                "city": "Los Angeles",
                "count": 8
            },
            {
                "city": "Chicago",
                "count": 2
            }
        ],
        "timeline": [
            {
                "date": "2024-01-15",
                "logins": 5
            },
            {
                "date": "2024-01-14",
                "logins": 3
            }
        ]
    }
}
```

## 🔔 Notification APIs

### Send New Session Notification

#### POST `/api/notifications/new-session`
Send notification for new device login.

**Headers:**
```http
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "device_id": 1,
    "message": "New device login detected",
    "type": "new_session"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Notification sent successfully",
    "notification_id": 456
}
```

## 🛡️ Security APIs

### Validate Session

#### GET `/api/logins/validate`
Validate current session.

**Headers:**
```http
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "valid": true,
    "login": {
        "id": 123,
        "created_at": "2024-01-15T10:30:00Z",
        "expires_at": "2024-01-16T10:30:00Z",
        "device": {
            "os": "android",
            "model": "Galaxy S20"
        }
    }
}
```

### Report Suspicious Login

#### POST `/api/security/suspicious-login`
Report suspicious login attempt.

**Headers:**
```http
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "ip": "192.168.1.100",
    "user_agent": "Mozilla/5.0...",
    "reason": "multiple_failed_attempts",
    "details": "5 failed attempts in 10 minutes"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Suspicious activity logged",
    "security_log_id": 789
}
```

## 📝 Error Responses

### 400 Bad Request
```json
{
    "success": false,
    "message": "Invalid data",
    "errors": {
        "email": ["Email is required"],
        "password": ["Password is required"]
    }
}
```

### 401 Unauthorized
```json
{
    "success": false,
    "message": "Unauthorized access",
    "error_code": "UNAUTHORIZED"
}
```

### 403 Forbidden
```json
{
    "success": false,
    "message": "Action not allowed",
    "error_code": "FORBIDDEN"
}
```

### 404 Not Found
```json
{
    "success": false,
    "message": "Session not found",
    "error_code": "LOGIN_NOT_FOUND"
}
```

### 429 Too Many Requests
```json
{
    "success": false,
    "message": "Rate limit exceeded",
    "error_code": "RATE_LIMIT_EXCEEDED",
    "retry_after": 60
}
```

### 500 Internal Server Error
```json
{
    "success": false,
    "message": "Internal server error",
    "error_code": "INTERNAL_ERROR"
}
```

## 🔧 Rate Limiting

### Request Limits

| Endpoint | Limit | Window |
|----------|-------|--------|
| `/login` | 5 | 1 minute |
| `/api/logins/*` | 60 | 1 minute |
| `/api/devices/*` | 30 | 1 minute |
| `/api/notifications/*` | 10 | 1 minute |

### Rate Limiting Headers

```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1642248000
Retry-After: 60
```

## 📱 Mobile App Integration

### Flutter Example

```dart
class AuthTrackerService {
  static const String baseUrl = 'https://api.example.com';
  
  Future<Map<String, dynamic>> getActiveLogins() async {
    final response = await http.get(
      Uri.parse('$baseUrl/api/logins/active'),
      headers: {
        'Authorization': 'Bearer ${await getToken()}',
        'Content-Type': 'application/json',
      },
    );
    
    return json.decode(response.body);
  }
  
  Future<Map<String, dynamic>> logoutLogin(int loginId) async {
    final response = await http.post(
      Uri.parse('$baseUrl/api/logins/$loginId/logout'),
      headers: {
        'Authorization': 'Bearer ${await getToken()}',
        'Content-Type': 'application/json',
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

// Add token to requests
authTrackerAPI.interceptors.request.use((config) => {
  const token = getToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export const getActiveLogins = () => {
  return authTrackerAPI.get('/api/logins/active');
};

export const logoutLogin = (loginId) => {
  return authTrackerAPI.post(`/api/logins/${loginId}/logout`);
};
```

---

**📚 This reference covers all available APIs in Laravel Auth Tracker.**