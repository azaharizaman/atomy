<?php

declare(strict_types=1);

namespace Nexus\Export\Exceptions;

/**
 * Invalid definition exception
 * 
 * Thrown when ExportDefinition fails schema validation
 */
class InvalidDefinitionException extends ExportException
{
    /**
     * Create from validation errors
     * 
     * @param array<string, mixed> $errors Validation errors
     */
    public static function fromValidationErrors(array $errors): self
    {
        $message = 'Export definition validation failed: ' . json_encode($errors);
        return new self($message);
    }
}
