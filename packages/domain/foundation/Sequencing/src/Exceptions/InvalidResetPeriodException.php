<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Exceptions;

use Exception;

/**
 * Exception thrown when an invalid reset period is provided.
 */
class InvalidResetPeriodException extends Exception
{
    public static function unknownPeriod(string $period): self
    {
        return new self("Invalid reset period: '{$period}'. Must be one of: never, daily, monthly, yearly");
    }
}
