<?php

declare(strict_types=1);

namespace Nexus\Import\Exceptions;

/**
 * Thrown when business rule validation fails at row level
 * 
 * Note: This is used internally for system-level validation failures.
 * Row-level data validation failures are collected as ImportError objects.
 */
class ValidationException extends ImportException
{
    public function __construct(
        string $rule,
        string $reason,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $message = "Validation failed for rule '{$rule}': {$reason}";
        parent::__construct($message, $code, $previous);
    }
}
