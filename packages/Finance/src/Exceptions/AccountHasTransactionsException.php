<?php

declare(strict_types=1);

namespace Nexus\Finance\Exceptions;

final class AccountHasTransactionsException extends FinanceException
{
    public static function forAccount(string $accountId, int $transactionCount): self
    {
        return new self(
            "Cannot delete account {$accountId} because it has {$transactionCount} associated transactions"
        );
    }
}
