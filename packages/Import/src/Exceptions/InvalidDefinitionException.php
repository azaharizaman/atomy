<?php

declare(strict_types=1);

namespace Nexus\Import\Exceptions;

/**
 * Thrown when import definition fails schema validation
 */
class InvalidDefinitionException extends ImportException
{
    /**
     * @param array<string> $errors Validation errors
     */
    public function __construct(
        array $errors,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $message = 'Import definition validation failed: ' . implode('; ', $errors);
        parent::__construct($message, $code, $previous);
    }
}
