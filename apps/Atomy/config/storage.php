<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Storage Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the Nexus\Storage package. The "local" disk is used by default.
    |
    */

    'default' => env('STORAGE_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Configuration for various storage disks. Each disk can use a different
    | driver (local, s3, etc.) and has its own configuration options.
    |
    */

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Temporary URL Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for generating temporary signed URLs.
    |
    */

    'temporary_urls' => [
        'default_expiration' => 3600, // 1 hour in seconds
        'max_expiration' => 86400, // 24 hours in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for file upload operations.
    |
    */

    'uploads' => [
        'max_size' => env('STORAGE_MAX_UPLOAD_SIZE', 10 * 1024 * 1024), // 10 MB default
        'allowed_mime_types' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Path Validation
    |--------------------------------------------------------------------------
    |
    | Security settings for path validation.
    |
    */

    'path_validation' => [
        'allow_absolute_paths' => false,
        'block_directory_traversal' => true,
        'enforce_forward_slashes' => true,
    ],
];
