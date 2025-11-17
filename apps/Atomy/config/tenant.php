<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant Identification Strategy
    |--------------------------------------------------------------------------
    |
    | How tenants are identified from incoming requests.
    | Supported: 'domain', 'subdomain', 'header', 'path', 'token'
    |
    */
    'identification_strategy' => env('TENANT_IDENTIFICATION_STRATEGY', 'subdomain'),

    /*
    |--------------------------------------------------------------------------
    | Identification Configuration
    |--------------------------------------------------------------------------
    |
    | Strategy-specific configuration
    |
    */
    'header_name' => env('TENANT_HEADER_NAME', 'X-Tenant-ID'),
    'path_prefix' => env('TENANT_PATH_PREFIX', '/tenant/'),

    /*
    |--------------------------------------------------------------------------
    | Central Domain
    |--------------------------------------------------------------------------
    |
    | The main application domain (used for subdomain strategy)
    |
    */
    'central_domain' => env('APP_DOMAIN', 'localhost'),

    /*
    |--------------------------------------------------------------------------
    | Default Tenant Settings
    |--------------------------------------------------------------------------
    |
    | Default values for new tenants
    |
    */
    'defaults' => [
        'timezone' => 'UTC',
        'locale' => 'en',
        'currency' => 'USD',
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i:s',
    ],

    /*
    |--------------------------------------------------------------------------
    | Enterprise Features
    |--------------------------------------------------------------------------
    */
    'features' => [
        'parent_child_tenants' => env('TENANT_PARENT_CHILD_ENABLED', false),
        'storage_quotas' => env('TENANT_STORAGE_QUOTAS_ENABLED', false),
        'rate_limiting' => env('TENANT_RATE_LIMITING_ENABLED', false),
        'impersonation' => env('TENANT_IMPERSONATION_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('TENANT_CACHE_ENABLED', true),
        'ttl' => env('TENANT_CACHE_TTL', 3600), // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Database Strategy
    |--------------------------------------------------------------------------
    |
    | Whether to use separate databases per tenant
    |
    */
    'multi_database' => env('TENANT_MULTI_DATABASE', false),
    'database_prefix' => env('TENANT_DATABASE_PREFIX', 'tenant_'),

    /*
    |--------------------------------------------------------------------------
    | Soft Delete Retention
    |--------------------------------------------------------------------------
    |
    | Days to keep archived tenants before permanent deletion
    |
    */
    'retention_days' => env('TENANT_RETENTION_DAYS', 90),
];
