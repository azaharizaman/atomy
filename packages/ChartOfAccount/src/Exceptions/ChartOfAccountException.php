<?php

declare(strict_types=1);

namespace Nexus\ChartOfAccount\Exceptions;

use Exception;

/**
 * Base exception for Chart of Account package.
 *
 * All chart of account specific exceptions extend this class,
 * allowing consumers to catch all package exceptions with a single type.
 */
class ChartOfAccountException extends Exception
{
    /**
     * Create a new ChartOfAccountException.
     *
     * @param string $message Exception message
     * @param int $code Error code (default 0)
     * @param \Throwable|null $previous Previous exception for chaining
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
