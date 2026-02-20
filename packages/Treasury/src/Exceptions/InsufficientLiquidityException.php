<?php

declare(strict_types=1);

namespace Nexus\Treasury\Exceptions;

final class InsufficientLiquidityException extends TreasuryException
{
    public static function forAmount(string $poolId, string $currency, float $available, float $requested): self
    {
        return new self("Insufficient liquidity in pool {$poolId}: available {$available} {$currency}, requested {$requested} {$currency}");
    }

    public static function forPool(string $poolId): self
    {
        return new self("Insufficient liquidity in pool: {$poolId}");
    }
}
