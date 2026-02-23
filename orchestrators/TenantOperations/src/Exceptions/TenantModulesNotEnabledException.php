<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Exceptions;

/**
 * Exception thrown when required modules are not enabled.
 */
class TenantModulesNotEnabledException extends TenantValidationException
{
    /**
     * @param array<int, string> $missingModules
     */
    public static function forTenant(string $tenantId, array $missingModules): self
    {
        return new self(
            "Tenant '{$tenantId}' does not have required modules: " . implode(', ', $missingModules),
            ['tenant_id' => $tenantId, 'missing_modules' => $missingModules]
        );
    }
}
