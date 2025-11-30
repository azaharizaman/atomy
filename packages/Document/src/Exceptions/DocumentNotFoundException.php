<?php

declare(strict_types=1);

namespace Nexus\Document\Exceptions;

/**
 * Exception thrown when a document is not found.
 */
class DocumentNotFoundException extends \RuntimeException
{
    public function __construct(
        public readonly string $documentId,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $message = $message ?: "Document not found: {$documentId}";
        parent::__construct($message, $code, $previous);
    }
}
