<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Exceptions;

/**
 * Base exception for feature flag operations.
 */
class FeatureFlagException extends SettingsManagementException
{
}

/**
 * Exception thrown when a feature flag is not found.
 */
class FeatureFlagNotFoundException extends FeatureFlagException
{
    public static function forKey(string $key, ?string $tenantId = null): self
    {
        return new self(
            message: "Feature flag '{$key}' not found" . ($tenantId ? " for tenant {$tenantId}" : ''),
            context: ['key' => $key, 'tenant_id' => $tenantId]
        );
    }
}

/**
 * Exception thrown when feature flag evaluation fails.
 */
class FeatureFlagEvaluationException extends FeatureFlagException
{
    public static function evaluationFailed(string $key, string $reason): self
    {
        return new self(
            message: "Failed to evaluate feature flag '{$key}': {$reason}",
            context: ['key' => $key, 'reason' => $reason]
        );
    }
}
