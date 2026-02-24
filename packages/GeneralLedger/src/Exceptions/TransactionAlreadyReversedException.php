<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Exceptions;

/**
 * Transaction Already Reversed Exception
 * 
 * Thrown when attempting to reverse a transaction that has already been reversed.
 */
final class TransactionAlreadyReversedException extends GeneralLedgerException
{
    public function __construct(string $transactionId)
    {
        parent::__construct(
            sprintf('Transaction %s has already been reversed', $transactionId)
        );
    }
}
