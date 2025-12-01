<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Exceptions;

/**
 * Exception thrown when a section is invalid.
 */
final class InvalidSectionException extends \InvalidArgumentException
{
    public function __construct(
        string $message = 'Invalid section',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
