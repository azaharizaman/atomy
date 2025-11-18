<?php

declare(strict_types=1);

namespace Nexus\DataProcessor\Exceptions;

/**
 * Thrown when document type is not supported
 */
final class UnsupportedDocumentTypeException extends DataProcessorException
{
    public static function forType(string $documentType): self
    {
        return new self("Document type not supported: {$documentType}");
    }
}
