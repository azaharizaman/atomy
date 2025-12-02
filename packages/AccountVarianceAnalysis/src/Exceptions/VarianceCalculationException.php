<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\Exceptions;

/**
 * Exception for variance calculation errors.
 */
class VarianceCalculationException extends \RuntimeException
{
    public function __construct(
        string $message = 'Variance calculation error',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
