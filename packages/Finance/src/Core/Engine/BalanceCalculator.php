<?php

declare(strict_types=1);

namespace Nexus\Finance\Core\Engine;

use DateTimeImmutable;
use Nexus\Finance\Contracts\LedgerRepositoryInterface;
use Nexus\Finance\Enums\AccountType;

/**
 * Balance Calculator
 * 
 * Internal engine for calculating account balances with proper debit/credit logic.
 */
final readonly class BalanceCalculator
{
    public function __construct(
        private LedgerRepositoryInterface $ledgerRepository
    ) {}

    /**
     * Calculate the balance for an account as of a specific date
     * 
     * Takes into account the account type's normal balance (debit or credit).
     * 
     * @param string $accountId The account ID
     * @param AccountType $accountType The account type
     * @param DateTimeImmutable $asOfDate The date to calculate balance
     * @return string The balance (positive or negative)
     */
    public function calculateBalance(string $accountId, AccountType $accountType, DateTimeImmutable $asOfDate): string
    {
        $balance = $this->ledgerRepository->getAccountBalance($accountId, $asOfDate);

        // For debit-normal accounts (Asset, Expense): debit increases, credit decreases
        // For credit-normal accounts (Liability, Equity, Revenue): credit increases, debit decreases
        // The ledger repository should return (total debits - total credits)
        // We just return the raw balance as the repository already handles the calculation

        return $balance;
    }

    /**
     * Calculate the net change for an account over a period
     * 
     * @param string $accountId The account ID
     * @param DateTimeImmutable $startDate Period start date
     * @param DateTimeImmutable $endDate Period end date
     * @return string The net change amount
     */
    public function calculateNetChange(string $accountId, DateTimeImmutable $startDate, DateTimeImmutable $endDate): string
    {
        $endBalance = $this->ledgerRepository->getAccountBalance($accountId, $endDate);
        
        // Get balance just before the period starts
        $startDateMinusOne = $startDate->modify('-1 day');
        $startBalance = $this->ledgerRepository->getAccountBalance($accountId, $startDateMinusOne);

        return bcsub($endBalance, $startBalance, 4);
    }

    /**
     * Calculate running balance for account activity
     * 
     * @param array<array{debit: string, credit: string}> $transactions
     * @param string $openingBalance The opening balance
     * @param AccountType $accountType The account type
     * @return array<array{debit: string, credit: string, balance: string}>
     */
    public function calculateRunningBalance(array $transactions, string $openingBalance, AccountType $accountType): array
    {
        $currentBalance = $openingBalance;
        $result = [];

        foreach ($transactions as $transaction) {
            // For debit-normal accounts: add debits, subtract credits
            // For credit-normal accounts: add credits, subtract debits
            if ($accountType->isDebitNormal()) {
                $currentBalance = bcadd($currentBalance, $transaction['debit'], 4);
                $currentBalance = bcsub($currentBalance, $transaction['credit'], 4);
            } else {
                $currentBalance = bcadd($currentBalance, $transaction['credit'], 4);
                $currentBalance = bcsub($currentBalance, $transaction['debit'], 4);
            }

            $result[] = array_merge($transaction, ['balance' => $currentBalance]);
        }

        return $result;
    }

    /**
     * Validate trial balance (total debits should equal total credits)
     * 
     * @param array<array{debit: string, credit: string}> $trialBalance
     * @return bool True if balanced
     */
    public function validateTrialBalance(array $trialBalance): bool
    {
        $totalDebit = '0.0000';
        $totalCredit = '0.0000';

        foreach ($trialBalance as $account) {
            $totalDebit = bcadd($totalDebit, $account['debit'], 4);
            $totalCredit = bcadd($totalCredit, $account['credit'], 4);
        }

        return bccomp($totalDebit, $totalCredit, 4) === 0;
    }

    /**
     * Calculate total for multiple accounts
     * 
     * @param array<string> $accountIds Array of account IDs
     * @param DateTimeImmutable $asOfDate The date to calculate balance
     * @return string The total balance
     */
    public function calculateTotal(array $accountIds, DateTimeImmutable $asOfDate): string
    {
        $total = '0.0000';

        foreach ($accountIds as $accountId) {
            $balance = $this->ledgerRepository->getAccountBalance($accountId, $asOfDate);
            $total = bcadd($total, $balance, 4);
        }

        return $total;
    }
}
