<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Exceptions;

/**
 * Cost Center Not Found Exception
 * 
 * Thrown when a requested cost center cannot be found.
 */
class CostCenterNotFoundException extends CostAccountingException
{
    public function __construct(
        private string $costCenterId
    ) {
        parent::__construct(
            sprintf('Cost center not found: %s', $costCenterId)
        );
    }

    public function getCostCenterId(): string
    {
        return $this->costCenterId;
    }
}
