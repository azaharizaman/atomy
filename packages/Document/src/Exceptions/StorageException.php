<?php

declare(strict_types=1);

namespace Nexus\Document\Exceptions;

/**
 * Exception thrown when storage operations fail.
 *
 * Wraps underlying storage layer exceptions.
 */
class StorageException extends \RuntimeException
{
    public function __construct(
        public readonly string $storagePath,
        public readonly string $operation,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $message = $message ?: "Storage {$operation} failed for path: {$storagePath}";
        parent::__construct($message, $code, $previous);
    }
}
