<?php

declare(strict_types=1);

namespace Nexus\Treasury\Exceptions;

final class InvestmentNotFoundException extends TreasuryException
{
    public static function forId(string $id): self
    {
        return new self("Investment not found with ID: {$id}");
    }

    public static function forTenant(string $tenantId): self
    {
        return new self("No investments found for tenant: {$tenantId}");
    }

    public static function forMaturity(string $investmentId): self
    {
        return new self("Investment {$investmentId} not found or not ready for maturity");
    }
}
