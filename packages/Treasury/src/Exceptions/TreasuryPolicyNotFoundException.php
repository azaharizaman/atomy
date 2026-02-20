<?php

declare(strict_types=1);

namespace Nexus\Treasury\Exceptions;

final class TreasuryPolicyNotFoundException extends TreasuryException
{
    public static function forId(string $id): self
    {
        return new self("Treasury policy not found with ID: {$id}");
    }

    public static function forTenant(string $tenantId): self
    {
        return new self("No treasury policy found for tenant: {$tenantId}");
    }

    public static function forName(string $name, string $tenantId): self
    {
        return new self("Treasury policy '{$name}' not found for tenant: {$tenantId}");
    }
}
