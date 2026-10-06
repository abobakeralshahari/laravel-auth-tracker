<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | The connection used by the package tables. Null means the default
    | application connection.
    |
    */

    'connection' => env('AUTH_TRACKER_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | "table_name" is kept for backward compatibility and holds the logins
    | table name. "devices_table" holds the devices table name.
    |
    */

    'table_name' => 'logins',

    'devices_table' => 'devices',

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | Override the models used by the package. Your classes must extend the
    | package models. "device_model" is kept for backward compatibility.
    |
    */

    'device_model' => Awsan\AuthTracker\Models\Device::class,

    'models' => [
        'login' => Awsan\AuthTracker\Models\Login::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Guards
    |--------------------------------------------------------------------------
    |
    | Which tracker driver handles each auth guard. Unlisted guards are
    | inferred from their auth driver: session, sanctum or passport.
    | Register custom drivers with AuthTracker::extend('jwt', fn () => ...).
    |
    | 'admin' => ['driver' => 'session'],
    | 'mobile' => ['driver' => 'jwt'],
    |
    */

    'guards' => [],

    /*
    |--------------------------------------------------------------------------
    | Trackables
    |--------------------------------------------------------------------------
    |
    | Per-model options. Keys are the authenticatable classes (subclasses
    | match too).
    |
    | max_sessions:  null for unlimited.
    | on_exceed:     revoke_oldest | reject | ask (dispatch SessionLimitExceeded only)
    | scope:         per_guard | global
    |
    | App\Models\Admin::class => ['max_sessions' => 1, 'on_exceed' => 'reject'],
    |
    */

    'trackables' => [],

    /*
    |--------------------------------------------------------------------------
    | Risk Assessment
    |--------------------------------------------------------------------------
    |
    | After each login the package compares it with the previous ones and
    | dispatches SuspiciousLogin when one of the enabled flags is raised.
    |
    */

    'risk' => [
        'enabled' => true,
        'flags' => [
            'new_device' => 30,
            'new_country' => 40,
            'impossible_travel' => 60,
        ],
        // Score from which SuspiciousLogin is dispatched.
        'threshold' => 30,
        // km/h above which a trip between two logins is impossible.
        'max_speed' => 900,
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Attempts
    |--------------------------------------------------------------------------
    */

    'attempts' => [
        'enabled' => true,
        // Credential keys holding the identifier of the attempt.
        'identifier_keys' => ['email', 'phone', 'username'],
        'retention_days' => 90,
    ],

    'attempts_table' => 'auth_attempts',

    /*
    |--------------------------------------------------------------------------
    | Sanctum Refresh Tokens
    |--------------------------------------------------------------------------
    |
    | AuthTracker::issueToken() / refreshToken(): short lived access tokens
    | renewed with a rotating refresh token bound to the login.
    |
    */

    'refresh' => [
        'access_lifetime' => 60,          // minutes
        'refresh_lifetime' => 60 * 24 * 30, // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | Used by "tracker:prune": revoked / expired logins older than this are
    | deleted, as well as devices unseen for "devices_days" without logins.
    |
    */

    'retention' => [
        'logins_days' => 90,
        'devices_days' => 180,
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity
    |--------------------------------------------------------------------------
    |
    | The last activity of a login is recorded at most once every
    | "touch_interval" seconds (0 to record every request).
    |
    */

    'activity' => [
        'touch_interval' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Remember Lifetime
    |--------------------------------------------------------------------------
    |
    | Lifetime of the remembered logins, in days.
    |
    */

    'remember_lifetime' => 365,

    /*
    |--------------------------------------------------------------------------
    | Passport
    |--------------------------------------------------------------------------
    |
    | Guards using the "passport" driver. The user provider of these guards
    | is used to resolve the owner of a newly created access token.
    |
    */

    'passport_guards' => ['api'],

    /*
    |--------------------------------------------------------------------------
    | Device Identification
    |--------------------------------------------------------------------------
    |
    | Headers and cookies inspected to identify the client device.
    |
    */

    'device' => [
        'header_prefix' => 'x-device-',
        'cookie'        => 'device_uuid',
        'cookie_lifetime' => 60 * 24 * 365,
        'fcm_cookies'   => ['notifyToken', 'fcmToken'],
        'tenant_header' => 'country-code',
    ],

    /*
    |--------------------------------------------------------------------------
    | Parser
    |--------------------------------------------------------------------------
    |
    | Choose which parser to use to parse the User-Agent.
    |
    | Supported values:
    | 'agent' (see https://github.com/jenssegers/agent)
    | 'whichbrowser' (see https://github.com/WhichBrowser/Parser-PHP)
    |
    */

    'parser' => 'agent',

    /*
    |--------------------------------------------------------------------------
    | IP Address Lookup
    |--------------------------------------------------------------------------
    |
    | Get additional data about the client IP (like the geolocation) by
    | calling an external API. HTTP providers require guzzlehttp/guzzle.
    |
    */

    'ip_lookup' => [

        /*
        | Supported values:
        | - 'ip2location-lite'
        | - 'ip-api'
        | - false (disabled)
        | - any key of the custom_providers array
        */

        'provider' => false,

        /*
        | Seconds to wait while connecting to the provider API. On timeout the
        | lookup is skipped and Awsan\AuthTracker\Events\FailedApiCall
        | is dispatched. Use 0 to wait indefinitely.
        */

        'timeout' => 1.0,

        'environments' => [
            'production',
        ],

        /*
        | Format: 'name_of_your_provider' => ProviderClassName::class
        */

        'custom_providers' => [],

        'ip2location' => [
            'ipv4_table' => 'ip2location_db3',
            'ipv6_table' => 'ip2location_db3_ipv6',
        ],
    ],
];
