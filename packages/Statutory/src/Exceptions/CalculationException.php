<?php

declare(strict_types=1);

namespace Nexus\Statutory\Exceptions;

/**
 * Exception thrown when statutory calculation fails.
 */
class CalculationException extends \RuntimeException
{
    public function __construct(string $deductionType, string $reason, ?\Throwable $previous = null)
    {
        parent::__construct(
            "Calculation failed for deduction type '{$deductionType}': {$reason}",
            0,
            $previous
        );
    }
}
