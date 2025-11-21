<?php

declare(strict_types=1);

namespace Nexus\Reporting\Exceptions;

/**
 * Thrown when a requested report definition cannot be found.
 */
class ReportNotFoundException extends ReportingException
{
    /**
     * Create exception for missing report definition.
     */
    public static function forId(string $reportId): self
    {
        return new self("Report definition with ID '{$reportId}' not found");
    }

    /**
     * Create exception for missing generated report.
     */
    public static function forGeneratedReport(string $reportGeneratedId): self
    {
        return new self("Generated report with ID '{$reportGeneratedId}' not found");
    }
}
