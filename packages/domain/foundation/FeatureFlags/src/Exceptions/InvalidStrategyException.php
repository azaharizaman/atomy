<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Exceptions;

/**
 * Exception thrown when an invalid evaluation strategy is encountered.
 *
 * Use Case: Defensive programming for unknown strategy types
 * (should never happen with enum-based strategy)
 */
final class InvalidStrategyException extends FeatureFlagException
{
    public static function forValue(string $value): self
    {
        return new self(
            "Invalid feature flag strategy: '{$value}'. " .
            "Expected one of: system_wide, percentage_rollout, tenant_list, user_list, custom."
        );
    }
}
