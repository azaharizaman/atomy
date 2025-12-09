<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Exceptions;

/**
 * Exception thrown when a memo is not found.
 */
class MemoNotFoundException extends \RuntimeException
{
    public function __construct(string $message = 'Memo not found', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
