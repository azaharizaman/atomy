<?php

declare(strict_types=1);

namespace Nexus\Document\Exceptions;

/**
 * Exception thrown when a user lacks permission for a document operation.
 */
class PermissionDeniedException extends \RuntimeException
{
    public function __construct(
        public readonly string $userId,
        public readonly string $documentId,
        public readonly string $action,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $message = $message ?: "User {$userId} does not have permission to {$action} document {$documentId}";
        parent::__construct($message, $code, $previous);
    }
}
