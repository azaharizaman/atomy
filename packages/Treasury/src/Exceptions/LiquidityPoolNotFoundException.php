<?php

declare(strict_types=1);

namespace Nexus\Treasury\Exceptions;

use Nexus\Treasury\Exceptions\TreasuryException;

/**
 * Exception thrown when liquidity pool is not found
 */
class LiquidityPoolNotFoundException extends TreasuryException
{
    public function __construct(string $poolId)
    {
        parent::__construct("Liquidity pool not found: {$poolId}");
    }
}
