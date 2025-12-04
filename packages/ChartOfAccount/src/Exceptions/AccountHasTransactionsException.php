<?php

declare(strict_types=1);

namespace Nexus\ChartOfAccount\Exceptions;

/**
 * Exception thrown when attempting to delete an account that has transactions.
 */
class AccountHasTransactionsException extends ChartOfAccountException
{
    /**
     * Create exception for account with transactions.
     *
     * @param string $id Account ID
     * @param int $transactionCount Number of transactions (optional)
     */
    public static function create(string $id, int $transactionCount = 0): self
    {
        $message = $transactionCount > 0
            ? sprintf('Cannot delete account %s: has %d transaction(s)', $id, $transactionCount)
            : sprintf('Cannot delete account %s: has transactions', $id);

        return new self($message);
    }
}
