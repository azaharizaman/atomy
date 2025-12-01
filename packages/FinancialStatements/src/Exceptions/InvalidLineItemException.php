<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Exceptions;

/**
 * Exception thrown when a line item is invalid.
 */
final class InvalidLineItemException extends \InvalidArgumentException
{
    public function __construct(
        string $message = 'Invalid line item',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
