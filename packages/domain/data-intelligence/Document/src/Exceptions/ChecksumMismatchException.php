<?php

declare(strict_types=1);

namespace Nexus\Document\Exceptions;

/**
 * Critical security exception thrown when document checksum verification fails.
 *
 * This indicates potential data corruption or tampering and should trigger
 * immediate security alerts.
 */
class ChecksumMismatchException extends \RuntimeException
{
    public function __construct(
        public readonly string $documentId,
        public readonly string $expectedChecksum,
        public readonly string $actualChecksum,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $message = $message ?: sprintf(
            'Checksum mismatch for document %s: expected %s, got %s. Possible data corruption or tampering.',
            $documentId,
            substr($expectedChecksum, 0, 16) . '...',
            substr($actualChecksum, 0, 16) . '...'
        );
        parent::__construct($message, $code, $previous);
    }
}
