<?php

declare(strict_types=1);

namespace Nexus\DataProcessor\Exceptions;

/**
 * Thrown when document processing fails
 */
final class ProcessingFailedException extends DataProcessorException
{
    public static function forDocument(string $filePath, string $reason): self
    {
        return new self("Failed to process document {$filePath}: {$reason}");
    }
}
