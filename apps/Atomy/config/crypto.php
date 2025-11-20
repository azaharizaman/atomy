<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Legacy Mode
    |--------------------------------------------------------------------------
    |
    | Enable legacy cryptography mode for gradual migration.
    | When true, packages will use their original crypto implementations.
    | When false, packages will use Nexus\Crypto interfaces.
    |
    | Set to false after testing to enable new crypto system.
    |
    */
    'legacy_mode' => env('CRYPTO_LEGACY_MODE', true),
    
    /*
    |--------------------------------------------------------------------------
    | Default Algorithms
    |--------------------------------------------------------------------------
    |
    | Default cryptographic algorithms for each operation type.
    | Can be overridden per operation in application code.
    |
    */
    'default_hasher' => env('CRYPTO_HASHER', 'sha256'),
    'default_encryptor' => env('CRYPTO_ENCRYPTOR', 'aes-256-gcm'),
    'default_signer' => env('CRYPTO_SIGNER', 'ed25519'),
    
    /*
    |--------------------------------------------------------------------------
    | Key Storage
    |--------------------------------------------------------------------------
    |
    | Configuration for encryption key storage.
    | Keys are stored encrypted using envelope encryption (APP_KEY encrypts DEKs).
    |
    */
    'key_storage' => [
        'driver' => env('CRYPTO_KEY_STORAGE', 'database'),
        'table' => 'encryption_keys',
        'rotation_history_table' => 'key_rotation_history',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Key Rotation
    |--------------------------------------------------------------------------
    |
    | Automated key rotation settings.
    | Keys are rotated via Nexus\Scheduler based on expiration date.
    |
    */
    'rotation' => [
        'enabled' => env('CRYPTO_ROTATION_ENABLED', true),
        'default_expiration_days' => env('CRYPTO_KEY_EXPIRATION_DAYS', 90),
        'warning_days' => env('CRYPTO_ROTATION_WARNING_DAYS', 7),
        'schedule_time' => env('CRYPTO_ROTATION_TIME', '03:00'), // 3 AM daily
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Supported Algorithms
    |--------------------------------------------------------------------------
    |
    | Whitelist of allowed algorithms for each category.
    | Used for validation and security policy enforcement.
    |
    */
    'algorithms' => [
        'hash' => [
            'sha256',
            'sha384',
            'sha512',
            'blake2b',
        ],
        
        'symmetric' => [
            'aes-256-gcm',      // Recommended (authenticated encryption)
            'aes-256-cbc',      // Legacy support
            'chacha20-poly1305', // Modern alternative
        ],
        
        'asymmetric' => [
            'hmac-sha256',      // HMAC signing
            'ed25519',          // Recommended (fast, secure)
            'rsa-2048',         // Legacy/compatibility
            'rsa-4096',         // High security
            'ecdsa-p256',       // Standards compliance
            // PQC algorithms (Phase 2 - not yet implemented)
            // 'dilithium3',
            // 'kyber768',
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Performance Tuning
    |--------------------------------------------------------------------------
    |
    | Performance-related settings for cryptographic operations.
    |
    */
    'performance' => [
        'cache_keys' => env('CRYPTO_CACHE_KEYS', true),
        'cache_ttl_seconds' => env('CRYPTO_CACHE_TTL', 3600),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | Enable audit logging for cryptographic operations.
    | Integrates with Nexus\AuditLogger for compliance.
    |
    */
    'audit' => [
        'enabled' => env('CRYPTO_AUDIT_ENABLED', true),
        'log_encryption' => env('CRYPTO_AUDIT_ENCRYPTION', true),
        'log_decryption' => env('CRYPTO_AUDIT_DECRYPTION', false), // Can be verbose
        'log_signing' => env('CRYPTO_AUDIT_SIGNING', true),
        'log_key_operations' => env('CRYPTO_AUDIT_KEYS', true),
    ],
];
