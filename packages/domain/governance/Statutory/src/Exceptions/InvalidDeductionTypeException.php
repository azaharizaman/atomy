<?php

declare(strict_types=1);

namespace Nexus\Statutory\Exceptions;

/**
 * Exception thrown when an invalid deduction type is referenced.
 */
class InvalidDeductionTypeException extends \RuntimeException
{
    public function __construct(string $deductionType, string $countryCode)
    {
        parent::__construct(
            "Invalid deduction type '{$deductionType}' for country '{$countryCode}'."
        );
    }
}
