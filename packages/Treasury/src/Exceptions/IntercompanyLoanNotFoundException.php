<?php

declare(strict_types=1);

namespace Nexus\Treasury\Exceptions;

final class IntercompanyLoanNotFoundException extends TreasuryException
{
    public static function forId(string $id): self
    {
        return new self("Intercompany loan not found with ID: {$id}");
    }

    public static function forEntities(string $fromEntityId, string $toEntityId): self
    {
        return new self(
            "Intercompany loan not found from entity {$fromEntityId} to entity {$toEntityId}"
        );
    }

    public static function forTenant(string $tenantId): self
    {
        return new self("No intercompany loans found for tenant: {$tenantId}");
    }
}
