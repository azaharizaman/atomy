<?php

declare(strict_types=1);

namespace Nexus\Document\Exceptions;

/**
 * Exception thrown when a document version is not found.
 */
class VersionNotFoundException extends \RuntimeException
{
    public function __construct(
        public readonly string $documentId,
        public readonly int $versionNumber,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $message = $message ?: "Version {$versionNumber} not found for document {$documentId}";
        parent::__construct($message, $code, $previous);
    }
}
