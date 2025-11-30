<?php

declare(strict_types=1);

namespace Nexus\Finance\Domain\Exceptions;

final class InvalidAccountException extends FinanceException
{
    public static function headerAccountNotPostable(string $accountCode): self
    {
        return new self("Account {$accountCode} is a header account and cannot have transactions posted to it");
    }

    public static function invalidData(string $reason): self
    {
        return new self("Invalid account data: {$reason}");
    }
}
