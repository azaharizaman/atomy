<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Exceptions;

/**
 * Base exception for all ProcurementOperations errors.
 */
class ProcurementOperationsException extends \Exception
{
    /**
     * Create a new exception.
     */
    public function __construct(
        string $message = 'Procurement operation failed',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
