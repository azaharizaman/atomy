<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Exceptions;

use RuntimeException;

/**
 * Bank Account Not Found Exception
 *
 * Thrown when a requested bank account does not exist.
 */
class BankAccountNotFoundException extends RuntimeException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Bank account with ID "%s" not found', $id));
    }

    public static function withAccountNumber(string $accountNumber): self
    {
        return new self(sprintf('Bank account with account number "%s" not found', $accountNumber));
    }
}
