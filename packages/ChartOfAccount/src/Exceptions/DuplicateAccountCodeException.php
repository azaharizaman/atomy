<?php

declare(strict_types=1);

namespace Nexus\ChartOfAccount\Exceptions;

/**
 * Exception thrown when attempting to create an account with a duplicate code.
 */
class DuplicateAccountCodeException extends ChartOfAccountException
{
    /**
     * Create exception for duplicate account code.
     *
     * @param string $code The duplicate code
     */
    public static function create(string $code): self
    {
        return new self(
            sprintf('Account with code "%s" already exists', $code)
        );
    }
}
