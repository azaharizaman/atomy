<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Exceptions;

/**
 * Exception thrown when a requested feature flag is not found.
 *
 * Use Case: Repository queries that expect a flag to exist
 *
 * Not thrown by FeatureFlagManager (which uses fail-closed default).
 */
final class FlagNotFoundException extends FeatureFlagException
{
    public static function forName(string $name, ?string $tenantId = null): self
    {
        $tenant = $tenantId !== null ? " for tenant '{$tenantId}'" : '';

        return new self("Feature flag '{$name}' not found{$tenant}.");
    }
}
