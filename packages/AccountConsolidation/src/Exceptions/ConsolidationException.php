<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\Exceptions;

/**
 * Base exception for consolidation errors.
 */
class ConsolidationException extends \RuntimeException
{
    public function __construct(
        string $message = 'Consolidation error',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
