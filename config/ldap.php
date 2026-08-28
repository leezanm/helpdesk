<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default LDAP Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the LDAP connections below you wish to use
    | as your default connection for all LDAP operations.
    |
    */

    'default' => env('LDAP_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | LDAP Connections
    |--------------------------------------------------------------------------
    |
    | Below you may configure each LDAP connection your application requires
    | access to. Migrated from the previous adldap2 configuration; values are
    | driven by the same LDAP_* environment variables.
    |
    */

    'connections' => [

        'default' => [
            'hosts' => explode(' ', env('LDAP_HOSTS', '127.0.0.1')),
            'username' => env('LDAP_USERNAME', 'cn=user,dc=local,dc=com'),
            'password' => env('LDAP_PASSWORD', 'secret'),
            'port' => (int) env('LDAP_PORT', 389),
            'base_dn' => env('LDAP_BASE_DN', 'dc=local,dc=com'),
            'timeout' => (int) env('LDAP_TIMEOUT', 5),
            'use_ssl' => (bool) env('LDAP_USE_SSL', false),
            'use_tls' => (bool) env('LDAP_USE_TLS', false),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | LDAP Logging
    |--------------------------------------------------------------------------
    |
    | When enabled, LDAP operations are logged to the default logging channel.
    | Useful for debugging connectivity. No credentials are logged.
    |
    */

    'logging' => env('LDAP_LOGGING', false),

    /*
    |--------------------------------------------------------------------------
    | LDAP Cache
    |--------------------------------------------------------------------------
    |
    | LDAP caching stores query results for the given time. Disabled by default.
    |
    */

    'cache' => [
        'enabled' => env('LDAP_CACHE', false),
        'driver' => env('CACHE_DRIVER', 'file'),
    ],

];
