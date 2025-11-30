<?php

declare(strict_types=1);

namespace Nexus\Statutory\Exceptions;

/**
 * Exception thrown when data extraction fails during report generation.
 */
class DataExtractionException extends \RuntimeException
{
    public function __construct(string $reportType, string $reason, ?\Throwable $previous = null)
    {
        parent::__construct(
            "Data extraction failed for report type '{$reportType}': {$reason}",
            0,
            $previous
        );
    }
}
