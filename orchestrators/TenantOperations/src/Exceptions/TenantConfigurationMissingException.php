<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Exceptions;

/**
 * Exception thrown when required configuration is missing.
 */
class TenantConfigurationMissingException extends TenantValidationException
{
    /**
     * @param array<int, string> $missingConfigs
     */
    public static function forTenant(string $tenantId, array $missingConfigs): self
    {
        return new self(
            "Tenant '{$tenantId}' is missing required configuration: " . implode(', ', $missingConfigs),
            ['tenant_id' => $tenantId, 'missing_configs' => $missingConfigs]
        );
    }
}
