<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Table Name
    |--------------------------------------------------------------------------
    |
    | Use this option to customize the name of the table used to save the
    | logins in the database.
    |
    */

    'table_name' => 'logins',

    /*
    |--------------------------------------------------------------------------
    | Remember Lifetime
    |--------------------------------------------------------------------------
    |
    | Here you can specify the lifetime of the remember tokens.
    |
    | Must be an integer representing the number of days.
    |
    */

    'remember_lifetime' => 365, // 1 year

    /*
    |--------------------------------------------------------------------------
    | Device Model
    |--------------------------------------------------------------------------
    |
    | The class name of the devices model.
    |
    */

    'device_model' => Alshahari\AuthTracker\Models\Device::class,

    /*
    |--------------------------------------------------------------------------
    | Device Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for device management and tracking.
    |
    */

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

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security settings for device tokens, API keys, and rate limiting.
    |
    */

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

    /*
    |--------------------------------------------------------------------------
    | Notifications Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for sending notifications about authentication events.
    |
    */

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
        
        'notification_classes' => [
            'new_device' => \App\Notifications\NewDeviceLoginNotification::class,
            'suspicious_login' => \App\Notifications\SuspiciousLoginNotification::class,
            'device_registration' => \App\Notifications\DeviceRegisteredNotification::class,
        ],
        
        'push_messages' => [
            'new_device' => 'New device login detected',
            'suspicious_login' => 'Suspicious login activity detected',
            'device_registration' => 'New device registered',
            'general_login' => 'Login successful',
        ],
        
        'webhook_url' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Parser
    |--------------------------------------------------------------------------
    |
    | Choose which parser to use to parse the User-Agent.
    | You will need to install the package of the corresponding parser.
    |
    | Supported values:
    | 'agent' (see https://github.com/jenssegers/agent)
    | 'whichbrowser' (see https://github.com/WhichBrowser/Parser-PHP)
    |
    */

    'parser' => 'whichbrowser',

    /*
    |--------------------------------------------------------------------------
    | IP Address Lookup
    |--------------------------------------------------------------------------
    |
    | This package provides a feature to get additional data about the client
    | IP (like the geolocation) by calling an external API.
    |
    | This feature makes usage of the Guzzle PHP HTTP client to make the API
    | calls, so you will have to install the corresponding package
    | (see https://github.com/guzzle/guzzle) if you are considering to enable
    | the IP address lookup.
    |
    */

    'ip_lookup' => [
        'provider' => false,
        'timeout' => 1.0,
        'environments' => ['production'],
        'custom_providers' => [],
        'ip2location' => [
            'ipv4_table' => 'ip2location_db3',
            'ipv6_table' => 'ip2location_db3_ipv6',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the service layer and middleware.
    |
    */

    'service' => [
        'auto_track_logins' => true,
        'auto_register_devices' => true,
        'middleware_priority' => 100,
        'log_events' => true,
        'cleanup_old_logs' => true,
        'cleanup_after_days' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | Specify the database connection to use for auth tracker tables.
    |
    */

    'connection' => null,
];
