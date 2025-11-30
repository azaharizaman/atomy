<?php

declare(strict_types=1);

namespace Nexus\Document\Exceptions;

/**
 * Exception thrown when a retention policy prevents document deletion.
 */
class RetentionPolicyViolationException extends \RuntimeException
{
    public function __construct(
        public readonly string $documentId,
        public readonly string $reason,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $message = $message ?: "Cannot delete document {$documentId}: {$reason}";
        parent::__construct($message, $code, $previous);
    }
}
