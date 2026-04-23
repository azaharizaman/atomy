<?php

declare(strict_types=1);

use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;

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

    'quote_intelligence' => [
        'mode' => (string) env('QUOTE_INTELLIGENCE_MODE', 'deterministic'),
        'llm' => [
            'provider' => (string) env('QUOTE_INTELLIGENCE_LLM_PROVIDER', ''),
            'model' => (string) env('QUOTE_INTELLIGENCE_LLM_MODEL', ''),
            'base_url' => (string) env('QUOTE_INTELLIGENCE_LLM_BASE_URL', ''),
            'api_key' => (string) env('QUOTE_INTELLIGENCE_LLM_API_KEY', ''),
            'timeout_seconds' => (int) env('QUOTE_INTELLIGENCE_LLM_TIMEOUT_SECONDS', 30),
        ],
    ],

    'ai' => [
        'mode' => (string) env('AI_MODE', AiStatusSchema::MODE_DETERMINISTIC),
        'provider' => [
            'name' => (string) env('HF_PROVIDER_NAME', 'huggingface'),
            'default_auth_token' => (string) env('HF_DEFAULT_AUTH_TOKEN', (string) env('HF_AUTH_TOKEN', '')),
            'default_timeout_seconds' => (int) env('HF_DEFAULT_TIMEOUT_SECONDS', (int) env('HF_TIMEOUT_SECONDS', 10)),
        ],
        'endpoints' => [
            'document' => [
                'uri' => (string) env('HF_DOCUMENT_ENDPOINT', ''),
                'auth_token' => (string) env('HF_DOCUMENT_AUTH_TOKEN', ''),
                'timeout_seconds' => (int) env('HF_DOCUMENT_TIMEOUT_SECONDS', 10),
                'health_url' => (string) env('HF_DOCUMENT_HEALTH_URL', ''),
                'health_path' => (string) env('HF_DOCUMENT_HEALTH_PATH', '/health'),
                'health_method' => (string) env('HF_DOCUMENT_HEALTH_METHOD', 'GET'),
            ],
            'normalization' => [
                'uri' => (string) env('HF_NORMALIZATION_ENDPOINT', ''),
                'auth_token' => (string) env('HF_NORMALIZATION_AUTH_TOKEN', ''),
                'timeout_seconds' => (int) env('HF_NORMALIZATION_TIMEOUT_SECONDS', 10),
                'health_url' => (string) env('HF_NORMALIZATION_HEALTH_URL', ''),
                'health_path' => (string) env('HF_NORMALIZATION_HEALTH_PATH', '/health'),
                'health_method' => (string) env('HF_NORMALIZATION_HEALTH_METHOD', 'GET'),
            ],
            'sourcing_recommendation' => [
                'uri' => (string) env('HF_SOURCING_RECOMMENDATION_ENDPOINT', ''),
                'auth_token' => (string) env('HF_SOURCING_RECOMMENDATION_AUTH_TOKEN', ''),
                'timeout_seconds' => (int) env('HF_SOURCING_RECOMMENDATION_TIMEOUT_SECONDS', 10),
                'health_url' => (string) env('HF_SOURCING_RECOMMENDATION_HEALTH_URL', ''),
                'health_path' => (string) env('HF_SOURCING_RECOMMENDATION_HEALTH_PATH', '/health'),
                'health_method' => (string) env('HF_SOURCING_RECOMMENDATION_HEALTH_METHOD', 'GET'),
            ],
            'comparison_award' => [
                'uri' => (string) env('HF_COMPARISON_AWARD_ENDPOINT', ''),
                'auth_token' => (string) env('HF_COMPARISON_AWARD_AUTH_TOKEN', ''),
                'timeout_seconds' => (int) env('HF_COMPARISON_AWARD_TIMEOUT_SECONDS', 10),
                'health_url' => (string) env('HF_COMPARISON_AWARD_HEALTH_URL', ''),
                'health_path' => (string) env('HF_COMPARISON_AWARD_HEALTH_PATH', '/health'),
                'health_method' => (string) env('HF_COMPARISON_AWARD_HEALTH_METHOD', 'GET'),
            ],
            'insight' => [
                'uri' => (string) env('HF_INSIGHT_ENDPOINT', ''),
                'auth_token' => (string) env('HF_INSIGHT_AUTH_TOKEN', ''),
                'timeout_seconds' => (int) env('HF_INSIGHT_TIMEOUT_SECONDS', 10),
                'health_url' => (string) env('HF_INSIGHT_HEALTH_URL', ''),
                'health_path' => (string) env('HF_INSIGHT_HEALTH_PATH', '/health'),
                'health_method' => (string) env('HF_INSIGHT_HEALTH_METHOD', 'GET'),
            ],
            'governance' => [
                'uri' => (string) env('HF_GOVERNANCE_ENDPOINT', ''),
                'auth_token' => (string) env('HF_GOVERNANCE_AUTH_TOKEN', ''),
                'timeout_seconds' => (int) env('HF_GOVERNANCE_TIMEOUT_SECONDS', 10),
                'health_url' => (string) env('HF_GOVERNANCE_HEALTH_URL', ''),
                'health_path' => (string) env('HF_GOVERNANCE_HEALTH_PATH', '/health'),
                'health_method' => (string) env('HF_GOVERNANCE_HEALTH_METHOD', 'GET'),
            ],
        ],
    ],
];
