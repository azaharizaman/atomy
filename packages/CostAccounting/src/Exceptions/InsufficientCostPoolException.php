<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Exceptions;

/**
 * Insufficient Cost Pool Exception
 * 
 * Thrown when a cost pool does not have sufficient balance
 * to perform the requested allocation.
 */
class InsufficientCostPoolException extends CostAccountingException
{
    public function __construct(
        private string $poolId,
        private float $available,
        private float $requested
    ) {
        parent::__construct(
            sprintf(
                'Insufficient funds in cost pool %s: available %.2f, requested %.2f',
                $poolId,
                $available,
                $requested
            )
        );
    }

    public function getPoolId(): string
    {
        return $this->poolId;
    }

    public function getAvailable(): float
    {
        return $this->available;
    }

    public function getRequested(): float
    {
        return $this->requested;
    }
}
