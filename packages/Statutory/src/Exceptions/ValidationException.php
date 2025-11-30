<?php

declare(strict_types=1);

namespace Nexus\Statutory\Exceptions;

/**
 * Exception thrown when report validation fails.
 */
class ValidationException extends \RuntimeException
{
    /**
     * @param string $reportType The report type
     * @param array<string> $errors Validation errors
     */
    public function __construct(string $reportType, array $errors)
    {
        $errorMessage = implode(', ', $errors);
        parent::__construct(
            "Validation failed for report type '{$reportType}': {$errorMessage}"
        );
    }
}
