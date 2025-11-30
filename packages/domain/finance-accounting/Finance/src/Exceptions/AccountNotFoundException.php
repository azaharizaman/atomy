<?php

declare(strict_types=1);

namespace Nexus\Finance\Exceptions;

final class AccountNotFoundException extends FinanceException
{
    public static function forId(string $id): self
    {
        return new self("Account not found with ID: {$id}");
    }

    public static function forCode(string $code): self
    {
        return new self("Account not found with code: {$code}");
    }
}
