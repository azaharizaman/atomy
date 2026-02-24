<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Contracts;

use Nexus\GeneralLedger\Entities\TrialBalance;

/**
 * Trial Balance Query Interface
 * 
 * Read operations for trial balance generation and querying.
 */
interface TrialBalanceQueryInterface
{
    /**
     * Generate trial balance for a period
     * 
     * Creates a snapshot of all account balances as of the end of a period.
     * 
     * @param string $ledgerId Ledger ULID
     * @param string $periodId Period ULID
     * @return TrialBalance Generated trial balance
     */
    public function generateTrialBalance(string $ledgerId, string $periodId): TrialBalance;

    /**
     * Generate trial balance as of a specific date
     * 
     * @param string $ledgerId Ledger ULIDparam \DateTimeImmutable $asOf
     * @Date Balance as of date
     * @return TrialBalance Generated trial balance
     */
    public function generateTrialBalanceAsOfDate(string $ledgerId, \DateTimeImmutable $asOfDate): TrialBalance;

    /**
     * Get trial balance by ID
     * 
     * @param string $id TrialBalance ULID
     * @return TrialBalance|null The trial balance if found
     */
    public function findById(string $id): ?TrialBalance;

    /**
     * Find trial balances for a ledger
     * 
     * @param string $ledgerId Ledger ULID
     * @return array<TrialBalance> Trial balances for the ledger
     */
    public function findByLedger(string $ledgerId): array;

    /**
     * Find trial balances for a period
     * 
     * @param string $periodId Period ULID
     * @return array<TrialBalance> Trial balances for the period
     */
    public function findByPeriod(string $periodId): array;

    /**
     * Check if trial balance is balanced
     * 
     * @param string $trialBalanceId TrialBalance ULID
     * @return bool True if balanced
     */
    public function isBalanced(string $trialBalanceId): bool;

    /**
     * Get all account balances for a ledger as of a date
     * 
     * Used for account balance reporting.
     * 
     * @param string $ledgerId Ledger ULID
     * @param \DateTimeImmutable $asOfDate Balance as of date
     * @return array<string, \Nexus\GeneralLedger\ValueObjects\AccountBalance> Account balances keyed by account ID
     */
    public function getAllAccountBalances(string $ledgerId, \DateTimeImmutable $asOfDate): array;
}
