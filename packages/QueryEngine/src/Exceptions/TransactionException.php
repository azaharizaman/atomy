<?php

declare(strict_types=1);

namespace Nexus\QueryEngine\Exceptions;

/**
 * Thrown when a transaction operation fails
 */
class TransactionException extends AnalyticsException
{
    public function __construct(string $operation, string $reason, ?\Throwable $previous = null)
    {
        parent::__construct("Transaction {$operation} failed: {$reason}", 0, $previous);
    }
}
