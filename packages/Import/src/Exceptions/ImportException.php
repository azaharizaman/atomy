<?php

declare(strict_types=1);

namespace Nexus\Import\Exceptions;

/**
 * Base exception for all import-related errors
 */
class ImportException extends \Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
