<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Exceptions;

use RuntimeException;

/**
 * Reconciliation Exception
 *
 * Thrown when reconciliation process encounters an error.
 */
class ReconciliationException extends RuntimeException
{
    public static function statementNotFound(string $statementId): self
    {
        return new self(sprintf('Bank statement "%s" not found for reconciliation', $statementId));
    }

    public static function alreadyReconciled(string $statementId): self
    {
        return new self(sprintf('Bank statement "%s" is already reconciled', $statementId));
    }

    public static function noTransactions(string $statementId): self
    {
        return new self(sprintf('Bank statement "%s" has no transactions to reconcile', $statementId));
    }

    public static function invalidTolerance(string $reason): self
    {
        return new self(sprintf('Invalid reconciliation tolerance: %s', $reason));
    }
}
