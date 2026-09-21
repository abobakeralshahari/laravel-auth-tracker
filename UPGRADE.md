# Upgrade Guide

## From 1.x to 2.0

### Requirements

- PHP 8.2+, Laravel 11 or 12.
- Laravel Passport 13 / Laravel Sanctum 4 when tracking API tokens.
- `jenssegers/agent` is now a hard dependency. `whichbrowser/parser` is optional
  (`'parser' => 'whichbrowser'`), the default parser is now `agent`.

### Database

Run the migrations, the `upgrade_auth_tracker_tables_v2` migration is safe on
an existing 1.x database:

```bash
php artisan migrate
```

It performs the following changes:

| Table | Change |
|---|---|
| `devices` | Adds `type`, `browser`, `app_type`, `tenant_id`, `last_seen_at`, `metadata`. |
| `devices` | Widens `udid` to 100 chars; merges devices sharing the same `udid` (logins are moved to the oldest one) and makes `udid` **unique**. |
| `logins` | Renames `device` to **`device_name`** (the column was shadowing the `device()` relation). |
| `logins` | Adds indexes on `session_id`, `oauth_access_token_id`, `personal_access_token_id`, `expires_at`, `logout_at`, `remember_token` and a composite index for the active logins query. |

On large tables run it outside peak hours: adding indexes locks the table on
some MySQL versions.

### Breaking changes

- **`$login->device`** now returns the related `Device` model. The device name
  string is available as `$login->device_name`.
- **`AuthenticatesWithTracking`** trait removed. It relied on the removed
  `AuthenticatesUsers` trait of `laravel/ui` and is no longer needed: the
  package now stores the login id in the session, so a session id regeneration
  after login (Breeze, Fortify, Filament...) is handled automatically.
- **`tracker:install`** now only publishes the configuration and migrations. It
  no longer appends routes to `routes/web.php` (the referenced controller was
  never published).
- **Revoked logins can no longer be recalled** through their remember token.
  The `remember_token` is cleared on revocation and the `eloquent-tracked`
  provider ignores revoked logins.
- `EloquentQueryBuilder::revoke()` marks the logins as revoked (like
  `Login::revoke()`) instead of deleting them, and returns the number of
  revoked logins.
- `DeviceService` is constructed with an optional `Request`. The `tenant`
  attribute was renamed `tenant_id` and the hard dependency on the global
  `tenant()` helper was replaced by a resolver (see below).

### New configuration keys

Republish the configuration or add these keys to your `config/auth_tracker.php`:

```php
'connection' => env('AUTH_TRACKER_CONNECTION'),
'devices_table' => 'devices',
'models' => ['login' => Alshahari\AuthTracker\Models\Login::class],
'passport_guards' => ['api'],
'device' => [
    'header_prefix' => 'x-device-',
    'cookie' => 'device_uuid',
    'cookie_lifetime' => 60 * 24 * 365,
    'fcm_cookies' => ['notifyToken', 'fcmToken'],
    'tenant_header' => 'country-code',
],
```

### Tenant resolution

```php
use Alshahari\AuthTracker\AuthTracker;

// AppServiceProvider::boot()
AuthTracker::resolveTenantUsing(fn () => tenant()?->getTenantKey());
```

Without a resolver the package still calls `tenant()` when the helper exists.

### Behavior fixes worth knowing

- Sanctum tracking was silently disabled in 1.x (listener registered with a
  wrong class name). Dispatching `PersonalAccessTokenCreated` now creates a
  login.
- Requests without device headers (web browsers) no longer erase the
  `fcm_token` / `app_version` previously sent by the mobile application.
- The device is resolved once per request and shared between the
  `store.device` middleware and the login listeners.

## 2.1 — Tracker manager, drivers and session chaining

Run `php artisan migrate`: the `add_drivers_and_revocation_to_logins_table`
migration adds `guard`, `driver`, `credential_id`, `revoked_at`,
`revoked_reason`, `last_activity_at`, `rotations`, `last_rotated_at` to the
logins table and `name` to the devices table, then backfills them from the
legacy columns. Legacy columns are still written.

### New entry points

```php
use Alshahari\AuthTracker\Facades\AuthTracker;

AuthTracker::active($user);                 // active logins with their device
AuthTracker::history($user, days: 30);
AuthTracker::current();                     // login of the current request
AuthTracker::revoke($login, 'admin');
AuthTracker::revokeOthers($user);
AuthTracker::revokeAll($user);
AuthTracker::devices($user);
AuthTracker::revokeDevice($device);

// Customization (AppServiceProvider::boot)
AuthTracker::resolveTenantUsing(fn () => tenant()?->getTenantKey());
AuthTracker::resolveDeviceUsing(fn (Request $r) => DeviceSignal::fromRequest($r));
AuthTracker::extend('jwt', fn ($app) => new JwtTrackerDriver);
```

`config/auth_tracker.php` gains `guards`, `trackables` and `activity`.

### Events

- `SessionStarted($user, $login, $context)` replaces `Events\Login` (still
  dispatched, deprecated).
- `SessionRevoked($login, $reason)`, `SessionRotated($login, $previousCredentialId)`,
  `DeviceRegistered($device, $request)` are new.

### Behavior changes

- **Passport refresh no longer creates a new login.** The existing login is
  kept and its credential rotated (`rotations` is incremented).
- `$user->activeLogin()` / `historyLogin()` are deprecated in favor of
  `activeSessions()` / `sessionHistory()`.
- `isAuthenticatedBySession()` now means "the current login was issued by
  the session driver" instead of "the request has a session".
- `last_activity_at` is recorded at most once per `activity.touch_interval`
  seconds (default 60) through the cache.

### Removed

- `Factories\LoginFactory`, `Traits\ManagesLogins` (internal).
- `Alshahari\AuthTracker\AuthTracker` static class introduced in 2.0 is
  replaced by the `Alshahari\AuthTracker\Facades\AuthTracker` facade with
  the same method names.
- `DeviceService` and `DeviceFactory` are kept as deprecated shims over
  `Support\DeviceSignal` and `Actions\ResolveDevice`.

## 2.2 — Policies, routes and refreshable tokens

Run `php artisan migrate` (`add_policies_to_auth_tracker_tables`).

### New

- `trackables.*.max_sessions` with `on_exceed` (`revoke_oldest`, `reject`,
  `ask`) and `scope` (`per_guard`, `global`).
- Trusted / blocked devices: `AuthTracker::trustDevice()`, `isTrustedDevice()`,
  `blockDevice()`, the `device.not-blocked` middleware.
- Risk assessment: `risk_score` / `risk_flags` on logins and the
  `SuspiciousLogin` event.
- Failed attempts (`auth_attempts` table, `AuthAttemptFailed` event).
- Refreshable Sanctum tokens: `AuthTracker::issueToken()` / `refreshToken()`.
- `AuthTracker::routes()` with package controllers and API resources.
- `tracker:prune`, `tracker:doctor`, `AuthTracker::fake()`, model factories.

### Changed

- `Route::authTracker($prefix)` now registers the package API routes
  (`auth-tracker.*` names) instead of routes to an application controller
  that was never published.
- `tracker:install` registers `PruneCommand` and `DoctorCommand`.
