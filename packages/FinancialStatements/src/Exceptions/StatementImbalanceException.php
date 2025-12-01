<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Exceptions;

/**
 * Exception thrown when a statement does not balance.
 */
final class StatementImbalanceException extends \RuntimeException
{
    public function __construct(
        string $message = 'Statement does not balance',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
