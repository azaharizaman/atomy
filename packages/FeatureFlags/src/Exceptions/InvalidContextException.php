<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Exceptions;

/**
 * Exception thrown when evaluation context is missing required data.
 *
 * Example: PERCENTAGE_ROLLOUT strategy requires a stable identifier,
 * but context has no userId, sessionId, or tenantId.
 */
final class InvalidContextException extends FeatureFlagException
{
    /**
     * Create exception for missing stable identifier.
     *
     * @param string $flagName The flag name
     * @return self
     */
    public static function missingStableIdentifier(string $flagName): self
    {
        return new self(
            "Flag '{$flagName}' requires a stable identifier (userId, sessionId, or tenantId) " .
            "for PERCENTAGE_ROLLOUT strategy, but context has none"
        );
    }

    /**
     * Create exception for missing tenant ID.
     *
     * @param string $flagName The flag name
     * @return self
     */
    public static function missingTenantId(string $flagName): self
    {
        return new self(
            "Flag '{$flagName}' requires tenantId in context for TENANT_LIST strategy"
        );
    }

    /**
     * Create exception for missing user ID.
     *
     * @param string $flagName The flag name
     * @return self
     */
    public static function missingUserId(string $flagName): self
    {
        return new self(
            "Flag '{$flagName}' requires userId in context for USER_LIST strategy"
        );
    }
}
