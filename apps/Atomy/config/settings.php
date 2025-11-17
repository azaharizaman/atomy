<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Protected Setting Keys
    |--------------------------------------------------------------------------
    |
    | These setting keys are protected from override at user or tenant level.
    | They can only be modified at the application level (via config).
    |
    */
    'protected_keys' => [
        'app.key',
        'app.env',
        'app.debug',
        'database.default',
        'database.connections',
        'cache.default',
        'queue.default',
        'mail.default',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL Patterns
    |--------------------------------------------------------------------------
    |
    | Define cache TTL (in seconds) for different setting key patterns.
    | Settings matching these patterns will use the specified TTL.
    |
    */
    'cache_ttl_patterns' => [
        'user.*' => 3600,        // 1 hour for user settings
        'tenant.*' => 7200,      // 2 hours for tenant settings
        'app.*' => 86400,        // 24 hours for application settings
        'default' => 3600,       // Default TTL
    ],

    /*
    |--------------------------------------------------------------------------
    | Read-Only Settings
    |--------------------------------------------------------------------------
    |
    | Settings that cannot be modified after creation.
    |
    */
    'readonly_keys' => [
        'tenant.created_at',
        'tenant.subscription_plan',
        'user.registration_date',
    ],

    /*
    |--------------------------------------------------------------------------
    | Encrypted Settings
    |--------------------------------------------------------------------------
    |
    | Setting keys that should be encrypted at rest.
    |
    */
    'encrypted_keys' => [
        'api.secret_key',
        'api.oauth_token',
        'mail.password',
        'database.password',
        'aws.secret_key',
    ],

    /*
    |--------------------------------------------------------------------------
    | Setting Groups
    |--------------------------------------------------------------------------
    |
    | Logical grouping of settings for organization and UI rendering.
    |
    */
    'groups' => [
        'general' => [
            'label' => 'General Settings',
            'icon' => 'settings',
            'order' => 1,
        ],
        'appearance' => [
            'label' => 'Appearance',
            'icon' => 'palette',
            'order' => 2,
        ],
        'notifications' => [
            'label' => 'Notifications',
            'icon' => 'bell',
            'order' => 3,
        ],
        'security' => [
            'label' => 'Security & Privacy',
            'icon' => 'lock',
            'order' => 4,
        ],
        'integrations' => [
            'label' => 'Integrations',
            'icon' => 'link',
            'order' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Setting Values
    |--------------------------------------------------------------------------
    |
    | Default values for common settings if not found in any layer.
    |
    */
    'defaults' => [
        'timezone' => 'UTC',
        'locale' => 'en',
        'currency' => 'USD',
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i:s',
        'items_per_page' => 25,
        'theme' => 'light',
    ],
];
