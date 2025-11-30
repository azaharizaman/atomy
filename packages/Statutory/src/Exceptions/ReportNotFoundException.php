<?php

declare(strict_types=1);

namespace Nexus\Statutory\Exceptions;

/**
 * Exception thrown when a statutory report is not found.
 */
class ReportNotFoundException extends \RuntimeException
{
    public function __construct(string $identifier)
    {
        parent::__construct(
            "Statutory report not found: '{$identifier}'."
        );
    }
}
