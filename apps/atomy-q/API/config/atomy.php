<?php

declare(strict_types=1);

return [
    'api_version' => 'v1',
    'api_prefix' => 'api/v1',

    'pagination' => [
        'default_per_page' => (int) env('ATOMY_DEFAULT_PER_PAGE', 25),
        'max_per_page' => (int) env('ATOMY_MAX_PER_PAGE', 100),
    ],

    'upload' => [
        'max_file_size_mb' => (int) env('ATOMY_MAX_UPLOAD_MB', 50),
        'allowed_mime_types' => [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'text/csv',
        ],
    ],

    'mfa' => [
        'device_trust_days' => (int) env('ATOMY_MFA_DEVICE_TRUST_DAYS', 30),
    ],

    'approval' => [
        'sla_hours' => (int) env('ATOMY_APPROVAL_SLA_HOURS', 48),
        'max_snooze_hours' => (int) env('ATOMY_APPROVAL_MAX_SNOOZE_HOURS', 72),
    ],

    'comparison' => [
        'min_vendors_final' => (int) env('ATOMY_MIN_VENDORS_FINAL', 2),
        'min_vendors_preview' => (int) env('ATOMY_MIN_VENDORS_PREVIEW', 1),
        'confidence_threshold' => (float) env('ATOMY_CONFIDENCE_THRESHOLD', 0.7),
    ],
];
