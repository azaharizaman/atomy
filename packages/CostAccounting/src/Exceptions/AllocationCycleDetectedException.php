<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Exceptions;

/**
 * Allocation Cycle Detected Exception
 * 
 * Thrown when circular dependencies are detected
 * in cost allocation rules.
 */
class AllocationCycleDetectedException extends CostAccountingException
{
    public function __construct(
        private array $cyclePath
    ) {
        parent::__construct(
            sprintf(
                'Circular allocation dependency detected: %s',
                implode(' -> ', $cyclePath)
            )
        );
    }

    public function getCyclePath(): array
    {
        return $this->cyclePath;
    }
}
