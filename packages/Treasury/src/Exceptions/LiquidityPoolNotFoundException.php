<?php

declare(strict_types=1);

namespace Nexus\Treasury\Exceptions;

final class LiquidityPoolNotFoundException extends TreasuryException
{
    public static function forId(string $id): self
    {
        return new self("Liquidity pool not found with ID: {$id}");
    }

    public static function forTenant(string $tenantId): self
    {
        return new self("No liquidity pool found for tenant: {$tenantId}");
    }

    public static function forName(string $name, string $tenantId): self
    {
        return new self("Liquidity pool '{$name}' not found for tenant: {$tenantId}");
    }
}
