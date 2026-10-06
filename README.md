# Laravel Auth Tracker

Track every login of your users — web sessions, Sanctum tokens, Passport
tokens, Filament panels or your own guard — bound to the device it came
from. List them, revoke them one by one or all at once, limit concurrent
sessions, trust devices, detect suspicious logins and renew API tokens
with rotating refresh tokens.

Companion package: [laravel-push-manager](../laravel-push-manager)
(FCM tokens linked to devices and logins).

- PHP 8.2+, Laravel 11 / 12
- Sanctum 4, Passport 13 (optional)
- Upgrading from 1.x? See [UPGRADE.md](UPGRADE.md).

## Installation

```bash
composer require owaiskit/auth-tracker
php artisan tracker:install
php artisan migrate
```

```php
use OwaisKit\AuthTracker\Traits\AuthTracking;

class User extends Authenticatable
{
    use AuthTracking;
}
```

To let "remember me" sessions be revoked individually, use the package
user provider in `config/auth.php`:

```php
'providers' => [
    'users' => ['driver' => 'eloquent-tracked', 'model' => App\Models\User::class],
],
```

Run `php artisan tracker:doctor` to check the installation.

## How it works

```
request ──► ResolveDevice ──► Device (udid, os, app version, fcm token…)
                 │
   login event ──┴─► RecordLogin ──► Login (guard, driver, credential, device)
                                        │
                        EnforceSessionLimit · AssessRisk · SessionStarted
```

- **Session logins** (any guard with the `session` driver, Filament included)
  are tracked from Laravel's `Login` event. The login id is stored in the
  session, so a session id regeneration after login is handled.
- **Sanctum**: dispatch `OwaisKit\AuthTracker\Events\PersonalAccessTokenCreated`
  after `createToken()`, or use `AuthTracker::issueToken()` (see below).
- **Passport**: tracked from `AccessTokenCreated`; a refresh rotates the
  credential of the existing login instead of creating a new one.
- Every request: `last_activity_at` is refreshed (throttled, one write per
  minute at most).

Each guard is handled by a *tracker driver* (`session`, `sanctum`,
`passport`). Map guards explicitly or add your own driver:

```php
// config/auth_tracker.php
'guards' => [
    'admin' => ['driver' => 'session'],
    'mobile' => ['driver' => 'jwt'],
],

// AppServiceProvider::boot()
AuthTracker::extend('jwt', fn ($app) => new JwtTrackerDriver);
```

## Usage

```php
use OwaisKit\AuthTracker\Facades\AuthTracker;

// Through the model
$user->currentLogin();
$user->activeSessions();          // Collection<Login>, with device
$user->sessionHistory(days: 30);
$user->devices();
$user->logout($loginId);          // or the current one
$user->logoutOthers();
$user->logoutAll();

// Through the facade (same operations, plus admin ones)
AuthTracker::active($user, guard: 'admin');
AuthTracker::revoke($login, reason: 'admin');
AuthTracker::revokeAll($user, reason: 'password_change');
AuthTracker::revokeDevice($device);
AuthTracker::blockDevice($device);
```

### Devices

```php
$device = AuthTracker::currentDevice();

AuthTracker::renameDevice($device, 'My phone');
AuthTracker::trustDevice($device, CarbonInterval::days(30));   // e.g. skip 2FA
AuthTracker::isTrustedDevice();                                 // current device, current user
AuthTracker::blockDevice($device);                              // revokes its logins
```

Native applications identify themselves with headers
(`x-device-udid`, `x-device-os`, `x-device-os-version`, `x-device-manufacturer`,
`x-device-model`, `x-device-app-type`, `x-device-app-version`,
`x-device-fcm-token`); browsers are identified by a server generated id.
Add the `store.device` middleware to resolve the device on every request
(`store.device:api,true` makes the headers mandatory), and
`device.not-blocked` to reject blocked devices.

### Session limits

```php
'trackables' => [
    App\Models\User::class => ['max_sessions' => 5],                          // revoke the oldest
    App\Models\Admin::class => ['max_sessions' => 1, 'on_exceed' => 'reject'], // 409 / redirect back
    App\Models\Merchant::class => ['max_sessions' => 3, 'on_exceed' => 'ask', 'scope' => 'global'],
],
```

`ask` only dispatches `SessionLimitExceeded` and lets you decide.

### Suspicious logins

`SuspiciousLogin` is dispatched when a login comes from a new device, a
new country or implies an impossible travel (coordinates from your IP
lookup provider). Listen to it to notify the user or require a second
factor — the package never blocks by itself.

```php
Event::listen(SuspiciousLogin::class, function (SuspiciousLogin $event) {
    if ($event->has('new_device') && ! AuthTracker::isTrustedDevice()) {
        // ...
    }
});
```

### Refreshable Sanctum tokens

Short lived access tokens renewed with a rotating refresh token bound to
the login. Revoking the login (user, admin, "logout others") makes the
refresh fail; a refresh token used twice reveals a theft and revokes the
login.

```php
$issued = AuthTracker::issueToken($user, 'phone');
return $issued->toArray(); // access_token, expires_in, refresh_token, refresh_expires_in

$issued = AuthTracker::refreshToken($request->refresh_token);
```

### API routes

```php
// routes/api.php
AuthTracker::routes(prefix: 'account/security', middleware: ['auth:sanctum']);
```

| Method | URI | Name |
|---|---|---|
| GET | sessions | sessions.index |
| GET | sessions/history | sessions.history |
| GET | sessions/current | sessions.current |
| DELETE | sessions/{id} | sessions.destroy |
| DELETE | sessions | sessions.destroy-others |
| DELETE | sessions/all | sessions.destroy-all |
| GET | devices | devices.index |
| PATCH | devices/{id} | devices.update (rename) |
| POST / DELETE | devices/{id}/trust | devices.trust / devices.untrust |
| DELETE | devices/{id} | devices.destroy (revoke its sessions) |
| POST | token/refresh | token.refresh (public, throttled) |

Use `only:` / `except:` to pick routes; `SessionResource` and
`DeviceResource` shape the responses.

### Events

| Event | When |
|---|---|
| `DeviceRegistered($device, $request)` | a device is seen for the first time |
| `DeviceTrusted($device)` | a device is trusted |
| `SessionStarted($user, $login, $context)` | a login is recorded |
| `SessionRotated($login, $previousCredentialId)` | a token was refreshed |
| `SessionRevoked($login, $reason)` | a login is revoked |
| `SessionLimitExceeded($user, $login, $excess, $limit, $action)` | too many sessions |
| `SuspiciousLogin($user, $login, $flags, $score, $previous)` | risk flags raised |
| `AuthAttemptFailed($attempt)` | invalid credentials / lockout |

Policies such as "revoke everything when the password changes" or "email
the user on a new device" belong to your application: listen to the
events and call the facade.

## Maintenance

```php
// routes/console.php
Schedule::command('tracker:prune')->daily();
```

Deletes revoked / expired logins, failed attempts and orphan devices
according to `auth_tracker.retention`.

## Testing

```php
$fake = AuthTracker::fake();   // tracking stays active, events are recorded

$fake->assertSessionStarted($user);
$fake->assertRevoked($login, 'admin');
$fake->assertSuspicious($user, 'new_device');

Login::factory()->ownedBy($user)->sanctum($tokenId)->revoked()->create();
Device::factory()->trusted()->create();
```

## IP lookup

Enable `ip_lookup.provider` (`ip-api`, `ip2location-lite` or a custom
class implementing `Interfaces\IpProvider`) to store the country, region
and city of each login. Providers exposing `getLatitude()` /
`getLongitude()` enable impossible travel detection.

## License

MIT
