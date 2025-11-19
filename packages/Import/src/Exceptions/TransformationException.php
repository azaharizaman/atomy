<?php

declare(strict_types=1);

namespace Nexus\Import\Exceptions;

/**
 * Thrown when data transformation fails at system level
 * 
 * Note: Row-level transformation failures are collected as ImportError objects.
 * This exception is for critical system-level transformation errors.
 */
class TransformationException extends ImportException
{
    public function __construct(
        string $transformationRule,
        string $reason,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $message = "Transformation '{$transformationRule}' failed: {$reason}";
        parent::__construct($message, $code, $previous);
    }
}
