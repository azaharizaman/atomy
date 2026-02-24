<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Contracts;

use Nexus\GeneralLedger\Entities\Transaction;

/**
 * Transaction Persist Interface
 * 
 * Write operations for transaction data.
 */
interface TransactionPersistInterface
{
    /**
     * Save a transaction
     * 
     * @param Transaction $transaction The transaction to save
     * @return void
     */
    public function save(Transaction $transaction): void;

    /**
     * Save multiple transactions in a batch
     * 
     * Used for posting journal entries with multiple lines.
     * 
     * @param array<Transaction> $transactions Transactions to save
     * @return void
     */
    public function saveBatch(array $transactions): void;

    /**
     * Delete a transaction
     * 
     * Note: This should typically only be used for reversing transactions.
     * 
     * @param string $id Transaction ULID
     * @return void
     */
    public function delete(string $id): void;

    /**
     * Mark a transaction as reversed
     * 
     * @param string $id Transaction ULID
     * @param string $reversedById Reversal transaction ULID
     * @return void
     */
    public function markAsReversed(string $id, string $reversedById): void;

    /**
     * Update running balance for a transaction
     * 
     * @param string $id Transaction ULID
     * @param \Nexus\GeneralLedger\ValueObjects\AccountBalance $newBalance New running balance
     * @return void
     */
    public function updateRunningBalance(string $id, \Nexus\GeneralLedger\ValueObjects\AccountBalance $newBalance): void;
}
