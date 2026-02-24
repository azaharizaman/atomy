<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\GeneralLedger\Enums\BalanceType;
use Nexus\GeneralLedger\Enums\TransactionType;
use Nexus\GeneralLedger\ValueObjects\AccountBalance;

/**
 * Balance Calculation Interface
 * 
 * Interface for calculating account balances based on transactions.
 */
interface BalanceCalculationInterface
{
    /**
     * Get the current balance of an account
     */
    public function getAccountBalance(
        string $ledgerAccountId,
        ?\DateTimeImmutable $asOfDate = null,
    ): AccountBalance;

    /**
     * Get the balance for a specific period
     */
    public function getAccountBalanceForPeriod(
        string $ledgerAccountId,
        string $periodId,
    ): AccountBalance;

    /**
     * Calculate the new balance after a transaction
     */
    public function calculateNewBalance(
        AccountBalance $currentBalance,
        AccountBalance $transactionAmount,
        TransactionType $transactionType,
        BalanceType $accountBalanceType,
    ): AccountBalance;

    /**
     * Get totals for a period
     */
    public function getAccountTotals(
        string $ledgerAccountId,
        string $periodId,
    ): array;

    /**
     * Get balances for all accounts in a ledger
     */
    public function getAllAccountBalances(
        string $ledgerId,
        \DateTimeImmutable $asOfDate,
    ): array;

    /**
     * Calculate period activity
     */
    public function getPeriodActivity(
        string $ledgerAccountId,
        string $periodId,
    ): Money;
}
