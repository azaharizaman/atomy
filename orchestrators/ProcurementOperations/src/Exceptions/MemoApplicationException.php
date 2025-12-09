<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Exceptions;

/**
 * Exception thrown when memo application fails.
 */
class MemoApplicationException extends \RuntimeException
{
    public function __construct(string $message = 'Memo application failed', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
