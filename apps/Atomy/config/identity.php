<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Password Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for password security and validation rules.
    |
    */
    'password' => [
        'algorithm' => PASSWORD_ARGON2ID,
        'options' => [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 1,
        ],
        'min_length' => 12,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_special_chars' => true,
        'history_limit' => 5,
        'max_age_days' => 90,
        'breach_check_enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Account Lockout Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for account lockout after failed login attempts.
    |
    */
    'lockout' => [
        'enabled' => true,
        'threshold' => 5,
        'duration_minutes' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for session management and token generation.
    |
    */
    'session' => [
        'lifetime' => 120, // minutes
        'token_length' => 64,
        'cleanup_frequency' => 'daily', // daily, hourly
    ],

    /*
    |--------------------------------------------------------------------------
    | API Token Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for API token generation and management.
    |
    */
    'api_token' => [
        'token_length' => 64,
        'default_expiry_days' => 365,
        'cleanup_frequency' => 'daily',
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Factor Authentication (MFA)
    |--------------------------------------------------------------------------
    |
    | Configuration for MFA methods and settings.
    |
    */
    'mfa' => [
        'enabled' => true,
        'methods' => [
            'totp' => [
                'enabled' => true,
                'issuer' => env('APP_NAME', 'Nexus'),
                'algorithm' => 'sha1',
                'digits' => 6,
                'period' => 30,
            ],
            'sms' => [
                'enabled' => false,
            ],
            'email' => [
                'enabled' => true,
                'code_length' => 6,
                'code_lifetime' => 5, // minutes
            ],
        ],
        'trusted_devices_enabled' => true,
        'trusted_device_lifetime_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Single Sign-On (SSO)
    |--------------------------------------------------------------------------
    |
    | Configuration for SSO providers.
    |
    */
    'sso' => [
        'enabled' => false,
        'providers' => [
            // 'oauth2' => [...],
            // 'saml' => [...],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for permission and role management.
    |
    */
    'authorization' => [
        'wildcard_enabled' => true,
        'cache_enabled' => true,
        'cache_ttl' => 3600, // seconds
        'super_admin_role' => 'super-admin',
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | Configuration for identity-related audit logging.
    |
    */
    'audit' => [
        'enabled' => true,
        'log_successful_logins' => true,
        'log_failed_logins' => true,
        'log_password_changes' => true,
        'log_permission_checks' => false, // High volume
    ],
];
