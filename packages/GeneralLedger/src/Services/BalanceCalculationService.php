<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Services;

use Nexus\GeneralLedger\Contracts\LedgerAccountQueryInterface;
use Nexus\GeneralLedger\Contracts\TransactionQueryInterface;
use Nexus\GeneralLedger\Enums\BalanceType;
use Nexus\GeneralLedger\Enums\TransactionType;
use Nexus\Common\ValueObjects\Money;
use Nexus\GeneralLedger\ValueObjects\AccountBalance;

/**
 * Balance Calculation Service
 * 
 * Service for calculating account balances based on transactions.
 * Handles the complexity of debit/credit accounting rules.
 */
final readonly class BalanceCalculationService
{
    public function __construct(
        private TransactionQueryInterface $transactionQuery,
        private LedgerAccountQueryInterface $accountQuery,
    ) {}

    /**
     * Get the current balance of an account
     * 
     * @param string $ledgerAccountId LedgerAccount ULID
     * @param \DateTimeImmutable|null $asOfDate Balance as of date
     * @return AccountBalance Current balance
     */
    public function getAccountBalance(
        string $ledgerAccountId,
        ?\DateTimeImmutable $asOfDate = null,
    ): AccountBalance {
        $asOfDate ??= new \DateTimeImmutable();
        return $this->transactionQuery->getAccountBalance($ledgerAccountId, $asOfDate);
    }

    /**
     * Get the balance for a specific period
     * 
     * @param string $ledgerAccountId LedgerAccount ULID
     * @param string $periodId Period ULID
     * @return AccountBalance Period balance
     */
    public function getAccountBalanceForPeriod(
        string $ledgerAccountId,
        string $periodId,
    ): AccountBalance {
        return $this->transactionQuery->getAccountBalanceForPeriod($ledgerAccountId, $periodId);
    }

    /**
     * Calculate the new balance after a transaction
     * 
     * This applies the accounting rules:
     * - Debit-balanced accounts (Assets, Expenses): Debits increase, Credits decrease
     * - Credit-balanced accounts (Liabilities, Equity, Revenue): Credits increase, Debits decrease
     * 
     * @param AccountBalance $currentBalance Current account balance
     * @param AccountBalance $transactionAmount Transaction amount
     * @param TransactionType $transactionType Type of transaction (DEBIT or CREDIT)
     * @param BalanceType $accountBalanceType Account's natural balance type
     * @return AccountBalance New balance
     */
    public function calculateNewBalance(
        AccountBalance $currentBalance,
        AccountBalance $transactionAmount,
        TransactionType $transactionType,
        BalanceType $accountBalanceType,
    ): AccountBalance {
        // Get the monetary values
        $currentAmount = $currentBalance->amount;
        $transactionAmountVal = $transactionAmount->amount;

        // Calculate based on transaction type and account type
        $newAmount = match (true) {
            // Debit-balanced account (Assets/Expenses)
            $accountBalanceType === BalanceType::DEBIT => match ($transactionType) {
                TransactionType::DEBIT => $currentAmount->add($transactionAmountVal),
                TransactionType::CREDIT => $currentAmount->subtract($transactionAmountVal),
            },
            // Credit-balanced account (Liabilities/Equity/Revenue)
            $accountBalanceType === BalanceType::CREDIT => match ($transactionType) {
                TransactionType::CREDIT => $currentAmount->add($transactionAmountVal),
                TransactionType::DEBIT => $currentAmount->subtract($transactionAmountVal),
            },
            // No balance type
            default => $currentAmount,
        };

        // Determine new balance type
        $newBalanceType = match (true) {
            $newAmount->isZero() => BalanceType::NONE,
            $newAmount->isPositive() => $accountBalanceType,
            default => $accountBalanceType === BalanceType::DEBIT 
                ? BalanceType::CREDIT 
                : BalanceType::DEBIT,
        };

        return new AccountBalance(
            amount: $newAmount->abs(),
            balanceType: $newBalanceType,
        );
    }

    /**
     * Get totals for a period
     * 
     * @param string $ledgerAccountId LedgerAccount ULID
     * @param string $periodId Period ULID
     * @return array{total_debits: AccountBalance, total_credits: AccountBalance, net_balance: AccountBalance}
     */
    public function getAccountTotals(
        string $ledgerAccountId,
        string $periodId,
    ): array {
        $totalDebits = $this->transactionQuery->getTotalDebits($ledgerAccountId, $periodId);
        $totalCredits = $this->transactionQuery->getTotalCredits($ledgerAccountId, $periodId);

        // Calculate net balance
        $account = $this->accountQuery->findById($ledgerAccountId);
        $balanceType = $account?->balanceType ?? BalanceType::DEBIT;

        $netBalance = match ($balanceType) {
            BalanceType::DEBIT => new AccountBalance(
                amount: $totalDebits->amount->subtract($totalCredits->amount)->abs(),
                balanceType: $totalDebits->amount->compareTo($totalCredits->amount) > 0
                    ? BalanceType::DEBIT
                    : ($totalCredits->amount->compareTo($totalDebits->amount) > 0
                        ? BalanceType::CREDIT
                        : BalanceType::NONE),
            ),
            BalanceType::CREDIT => new AccountBalance(
                amount: $totalCredits->amount->subtract($totalDebits->amount)->abs(),
                balanceType: $totalCredits->amount->compareTo($totalDebits->amount) > 0
                    ? BalanceType::CREDIT
                    : ($totalDebits->amount->compareTo($totalCredits->amount) > 0
                        ? BalanceType::DEBIT
                        : BalanceType::NONE),
            ),
            default => AccountBalance::zero(),
        };

        return [
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'net_balance' => $netBalance,
        ];
    }

    /**
     * Get balances for all accounts in a ledger
     * 
     * @param string $ledgerId Ledger ULID
     * @param \DateTimeImmutable $asOfDate Balance as of date
     * @return array<string, AccountBalance> Balances keyed by account ID
     */
    public function getAllAccountBalances(
        string $ledgerId,
        \DateTimeImmutable $asOfDate,
    ): array {
        $accounts = $this->accountQuery->findByLedger($ledgerId);
        $balances = [];

        foreach ($accounts as $account) {
            $balances[$account->id] = $this->getAccountBalance($account->id, $asOfDate);
        }

        return $balances;
    }

    /**
     * Calculate period activity (total debits + total credits)
     * 
     * @param string $ledgerAccountId LedgerAccount ULID
     * @param string $periodId Period ULID
     * @return Money Total activity for the period
     */
    public function getPeriodActivity(
        string $ledgerAccountId,
        string $periodId,
    ): Money {
        $totals = $this->getAccountTotals($ledgerAccountId, $periodId);
        
        return $totals['total_debits']->amount->add($totals['total_credits']->amount);
    }
}
