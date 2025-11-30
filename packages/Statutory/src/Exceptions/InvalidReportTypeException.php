<?php

declare(strict_types=1);

namespace Nexus\Statutory\Exceptions;

/**
 * Exception thrown when an invalid report type is referenced.
 */
class InvalidReportTypeException extends \RuntimeException
{
    public function __construct(string $reportType)
    {
        parent::__construct(
            "Invalid statutory report type: '{$reportType}'."
        );
    }
}
