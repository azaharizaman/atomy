<?php

declare(strict_types=1);

namespace Nexus\Document\Exceptions;

/**
 * Exception thrown when an invalid document type is provided.
 */
class InvalidDocumentTypeException extends \InvalidArgumentException
{
    public function __construct(
        public readonly string $invalidType,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $message = $message ?: "Invalid document type: {$invalidType}";
        parent::__construct($message, $code, $previous);
    }
}
