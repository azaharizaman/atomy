<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\Exceptions;

/**
 * Base exception for period close errors.
 */
class PeriodCloseException extends \RuntimeException
{
    public function __construct(
        string $message = 'Period close error',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
