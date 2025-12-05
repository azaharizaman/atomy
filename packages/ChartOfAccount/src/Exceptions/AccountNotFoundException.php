<?php

declare(strict_types=1);

namespace Nexus\ChartOfAccount\Exceptions;

/**
 * Exception thrown when an account cannot be found.
 */
class AccountNotFoundException extends ChartOfAccountException
{
    /**
     * Create exception for account not found by ID.
     *
     * @param string $id Account ULID
     */
    public static function withId(string $id): self
    {
        return new self(
            sprintf('Account with ID "%s" not found', $id)
        );
    }

    /**
     * Create exception for account not found by code.
     *
     * @param string $code Account code
     */
    public static function withCode(string $code): self
    {
        return new self(
            sprintf('Account with code "%s" not found', $code)
        );
    }
}
