<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Exceptions;

/**
 * Cost Pool Not Found Exception
 * 
 * Thrown when a requested cost pool cannot be found.
 */
class CostPoolNotFoundException extends CostAccountingException
{
    public function __construct(
        private string $poolId
    ) {
        parent::__construct(
            sprintf('Cost pool not found: %s', $poolId)
        );
    }

    public function getPoolId(): string
    {
        return $this->poolId;
    }
}
