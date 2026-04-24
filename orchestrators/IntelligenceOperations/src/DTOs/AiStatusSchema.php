<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\DTOs;

use Nexus\IntelligenceOperations\Exceptions\AiStatusContractException;

final readonly class AiStatusSchema
{
    public const MODE_OFF = 'off';
    public const MODE_PROVIDER = 'provider';
    public const MODE_DETERMINISTIC = 'deterministic';

    public const HEALTH_DISABLED = 'disabled';
    public const HEALTH_HEALTHY = 'healthy';
    public const HEALTH_DEGRADED = 'degraded';
    public const HEALTH_UNAVAILABLE = 'unavailable';

    public const CAPABILITY_GROUP_DOCUMENT_INTELLIGENCE = 'document_intelligence';
    public const CAPABILITY_GROUP_NORMALIZATION_INTELLIGENCE = 'normalization_intelligence';
    public const CAPABILITY_GROUP_SOURCING_RECOMMENDATION_INTELLIGENCE = 'sourcing_recommendation_intelligence';
    public const CAPABILITY_GROUP_COMPARISON_INTELLIGENCE = 'comparison_intelligence';
    public const CAPABILITY_GROUP_AWARD_INTELLIGENCE = 'award_intelligence';
    public const CAPABILITY_GROUP_INSIGHT_INTELLIGENCE = 'insight_intelligence';
    public const CAPABILITY_GROUP_GOVERNANCE_INTELLIGENCE = 'governance_intelligence';

    public const ENDPOINT_GROUP_DOCUMENT = 'document';
    public const ENDPOINT_GROUP_NORMALIZATION = 'normalization';
    public const ENDPOINT_GROUP_SOURCING_RECOMMENDATION = 'sourcing_recommendation';
    public const ENDPOINT_GROUP_COMPARISON_AWARD = 'comparison_award';
    public const ENDPOINT_GROUP_INSIGHT = 'insight';
    public const ENDPOINT_GROUP_GOVERNANCE = 'governance';

    public const FALLBACK_UI_MODE_HIDE_AI_CONTROLS = 'hide_ai_controls';
    public const FALLBACK_UI_MODE_SHOW_UNAVAILABLE_MESSAGE = 'show_unavailable_message';
    public const FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER = 'show_manual_continuity_banner';

    public const CAPABILITY_STATUS_AVAILABLE = 'available';
    public const CAPABILITY_STATUS_DEGRADED = 'degraded';
    public const CAPABILITY_STATUS_DISABLED = 'disabled';
    public const CAPABILITY_STATUS_UNAVAILABLE = 'unavailable';

    public const DIAGNOSTIC_VALUE_REDACTED = 'diagnostic_value_redacted';

    public const ALLOWED_REASON_CODES = [
        'ai_disabled_by_config',
        'ai_not_required',
        'deterministic_fallback_mode',
        'endpoint_disabled_by_config',
        'endpoint_group_degraded',
        'endpoint_group_disabled',
        'endpoint_group_healthy',
        'endpoint_group_unavailable',
        'endpoint_not_configured',
        'endpoint_reason_redacted',
        'health_probe_failed',
        'health_probe_timeout',
        'manual_fallback_available',
        'provider_available',
        'provider_degraded',
        'provider_disabled',
        'provider_unavailable',
    ];

    public const ALLOWED_DIAGNOSTIC_KEYS = [
        'capability_group',
        'checked_at',
        'endpoint_group',
        'endpoint_health',
        'endpoint_latency_ms',
        'health',
        'latency_ms',
        'manual_fallback_available',
        'mode',
        'provider_name',
        'requires_ai',
        'status',
    ];

    /**
     * @return list<string>
     */
    public static function modes(): array
    {
        return [
            self::MODE_OFF,
            self::MODE_PROVIDER,
            self::MODE_DETERMINISTIC,
        ];
    }

    /**
     * @return list<string>
     */
    public static function healths(): array
    {
        return [
            self::HEALTH_DISABLED,
            self::HEALTH_HEALTHY,
            self::HEALTH_DEGRADED,
            self::HEALTH_UNAVAILABLE,
        ];
    }

    /**
     * @return list<string>
     */
    public static function capabilityGroups(): array
    {
        return [
            self::CAPABILITY_GROUP_DOCUMENT_INTELLIGENCE,
            self::CAPABILITY_GROUP_NORMALIZATION_INTELLIGENCE,
            self::CAPABILITY_GROUP_SOURCING_RECOMMENDATION_INTELLIGENCE,
            self::CAPABILITY_GROUP_COMPARISON_INTELLIGENCE,
            self::CAPABILITY_GROUP_AWARD_INTELLIGENCE,
            self::CAPABILITY_GROUP_INSIGHT_INTELLIGENCE,
            self::CAPABILITY_GROUP_GOVERNANCE_INTELLIGENCE,
        ];
    }

    /**
     * @return list<string>
     */
    public static function endpointGroups(): array
    {
        return [
            self::ENDPOINT_GROUP_DOCUMENT,
            self::ENDPOINT_GROUP_NORMALIZATION,
            self::ENDPOINT_GROUP_SOURCING_RECOMMENDATION,
            self::ENDPOINT_GROUP_COMPARISON_AWARD,
            self::ENDPOINT_GROUP_INSIGHT,
            self::ENDPOINT_GROUP_GOVERNANCE,
        ];
    }

    /**
     * @return list<string>
     */
    public static function fallbackUiModes(): array
    {
        return [
            self::FALLBACK_UI_MODE_HIDE_AI_CONTROLS,
            self::FALLBACK_UI_MODE_SHOW_UNAVAILABLE_MESSAGE,
            self::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
        ];
    }

    public static function assertMode(string $mode): void
    {
        self::assertAllowed('AI mode', $mode, self::modes());
    }

    public static function assertHealth(string $health): void
    {
        self::assertAllowed('AI health', $health, self::healths());
    }

    public static function assertCapabilityGroup(string $capabilityGroup): void
    {
        self::assertAllowed('AI capability group', $capabilityGroup, self::capabilityGroups());
    }

    public static function assertEndpointGroup(string $endpointGroup): void
    {
        self::assertAllowed('AI endpoint group', $endpointGroup, self::endpointGroups());
    }

    public static function assertFallbackUiMode(string $fallbackUiMode): void
    {
        self::assertAllowed('AI fallback UI mode', $fallbackUiMode, self::fallbackUiModes());
    }

    /**
     * @param list<string> $reasonCodes
     * @return list<string>
     */
    public static function sanitizeReasonCodes(array $reasonCodes): array
    {
        $normalized = [];

        foreach ($reasonCodes as $reasonCode) {
            $sanitized = self::sanitizeReasonCode($reasonCode);
            if ($sanitized === null) {
                continue;
            }

            $normalized[] = $sanitized;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param array<string, scalar|null> $diagnostics
     * @return array<string, scalar|null>
     */
    public static function sanitizeDiagnostics(array $diagnostics): array
    {
        $normalized = [];

        foreach ($diagnostics as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            $diagnosticKey = trim($key);
            if ($diagnosticKey === '' || !in_array($diagnosticKey, self::ALLOWED_DIAGNOSTIC_KEYS, true)) {
                continue;
            }

            $sanitizedValue = self::sanitizeDiagnosticValue($value);
            if ($sanitizedValue === null) {
                continue;
            }

            $normalized[$diagnosticKey] = $sanitizedValue;
        }

        ksort($normalized);

        return $normalized;
    }

    private static function sanitizeReasonCode(string $reasonCode): ?string
    {
        $normalized = trim($reasonCode);
        if ($normalized === '') {
            return null;
        }

        if (in_array($normalized, self::ALLOWED_REASON_CODES, true)) {
            return $normalized;
        }

        return 'endpoint_reason_redacted';
    }

    /**
     * @return int|float|bool|string|null
     */
    private static function sanitizeDiagnosticValue(mixed $value): int|float|bool|string|null
    {
        if (is_int($value) || is_float($value) || is_bool($value) || $value === null) {
            return $value;
        }

        if (!is_string($value)) {
            return self::DIAGNOSTIC_VALUE_REDACTED;
        }

        $normalized = preg_replace('/[\x00-\x1F\x7F]+/u', '', $value);
        if (!is_string($normalized)) {
            return self::DIAGNOSTIC_VALUE_REDACTED;
        }

        $normalized = trim($normalized);
        if ($normalized === '') {
            return null;
        }

        if (strlen($normalized) > 128) {
            return self::DIAGNOSTIC_VALUE_REDACTED;
        }

        if (self::looksSensitiveDiagnosticValue($normalized)) {
            return self::DIAGNOSTIC_VALUE_REDACTED;
        }

        return $normalized;
    }

    private static function looksSensitiveDiagnosticValue(string $value): bool
    {
        if (filter_var($value, FILTER_VALIDATE_URL) !== false) {
            return true;
        }

        if (preg_match('/^[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]{8,}$/', $value) === 1) {
            return true;
        }

        if (preg_match('/^(?:[A-Za-z0-9+\/_-]{16,}={0,2})$/', $value) === 1) {
            return true;
        }

        if (preg_match('/(?:secret|token|password|passwd|apikey|api[_-]?key)/i', $value) === 1) {
            return true;
        }

        return false;
    }

    /**
     * @param list<string> $allowedValues
     */
    private static function assertAllowed(string $subject, string $value, array $allowedValues): void
    {
        if (!in_array($value, $allowedValues, true)) {
            throw AiStatusContractException::invalidValue($subject);
        }
    }
}
