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

    'device_model' => Alshahari\AuthTracker\Models\Device::class,

    'models' => [
        'login' => Alshahari\AuthTracker\Models\Login::class,
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
        | lookup is skipped and Alshahari\AuthTracker\Events\FailedApiCall
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
