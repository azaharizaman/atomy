<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\GeneralLedger\Contracts\LedgerAccountQueryInterface;
use Nexus\GeneralLedger\Contracts\LedgerQueryInterface;
use Nexus\GeneralLedger\Contracts\TransactionQueryInterface;
use Nexus\GeneralLedger\Contracts\BalanceCalculationInterface;
use Nexus\GeneralLedger\Entities\TrialBalance;
use Nexus\GeneralLedger\Entities\TrialBalanceLine;
use Symfony\Component\Uid\Ulid;

/**
 * Trial Balance Service
 * 
 * Service for generating trial balance reports.
 */
final readonly class TrialBalanceService
{
    public function __construct(
        private LedgerQueryInterface $ledgerQuery,
        private LedgerAccountQueryInterface $accountQuery,
        private TransactionQueryInterface $transactionQuery,
        private BalanceCalculationInterface $balanceService,
    ) {}

    /**
     * Generate trial balance for a period
     * 
     * Creates a snapshot of all account balances as of the end of the period.
     * 
     * @param string $ledgerId Ledger ULID
     * @param string $periodId Period ULID
     * @return TrialBalance Generated trial balance
     */
    public function generateTrialBalance(string $ledgerId, string $periodId): TrialBalance
    {
        // Validate ledger exists
        $ledger = $this->ledgerQuery->findById($ledgerId);
        if ($ledger === null) {
            throw new \InvalidArgumentException(
                sprintf('Ledger not found: %s', $ledgerId)
            );
        }

        // Get all accounts in the ledger
        $accounts = $this->accountQuery->findByLedger($ledgerId);

        if (empty($accounts)) {
            throw new \InvalidArgumentException(
                sprintf('No accounts found in ledger: %s', $ledgerId)
            );
        }

        // Build trial balance lines
        $lines = [];
        
        foreach ($accounts as $account) {
            // Get balance for the period
            $balance = $this->balanceService->getAccountBalanceForPeriod(
                $account->id,
                $periodId
            );

            // Create debit and credit balances
            $debitBalance = Money::zero($ledger->currency);
            $creditBalance = Money::zero($ledger->currency);

            if ($balance->isDebit()) {
                $debitBalance = $balance->amount;
            } elseif ($balance->isCredit()) {
                $creditBalance = $balance->amount;
            }

            $lines[] = new TrialBalanceLine(
                ledgerAccountId: $account->id,
                accountCode: $account->accountCode,
                accountName: $account->accountName,
                currency: $ledger->currency,
                debitBalance: $debitBalance,
                creditBalance: $creditBalance,
            );
        }

        return TrialBalance::create(
            id: (string) Ulid::generate(),
            ledgerId: $ledgerId,
            periodId: $periodId,
            asOfDate: new \DateTimeImmutable(),
            lines: $lines,
        );
    }

    /**
     * Generate trial balance as of a specific date
     * 
     * @param string $ledgerId Ledger ULID
     * @param \DateTimeImmutable $asOfDate Balance as of date
     * @return TrialBalance Generated trial balance
     */
    public function generateTrialBalanceAsOfDate(
        string $ledgerId,
        \DateTimeImmutable $asOfDate,
    ): TrialBalance {
        // Validate ledger exists
        $ledger = $this->ledgerQuery->findById($ledgerId);
        if ($ledger === null) {
            throw new \InvalidArgumentException(
                sprintf('Ledger not found: %s', $ledgerId)
            );
        }

        // Get all accounts in the ledger
        $accounts = $this->accountQuery->findByLedger($ledgerId);

        if (empty($accounts)) {
            throw new \InvalidArgumentException(
                sprintf('No accounts found in ledger: %s', $ledgerId)
            );
        }

        // Build trial balance lines
        $lines = [];
        
        foreach ($accounts as $account) {
            // Get balance as of date
            $balance = $this->balanceService->getAccountBalance(
                $account->id,
                $asOfDate
            );

            // Create debit and credit balances
            $debitBalance = Money::zero($ledger->currency);
            $creditBalance = Money::zero($ledger->currency);

            if ($balance->isDebit()) {
                $debitBalance = $balance->amount;
            } elseif ($balance->isCredit()) {
                $creditBalance = $balance->amount;
            }

            $lines[] = new TrialBalanceLine(
                ledgerAccountId: $account->id,
                accountCode: $account->accountCode,
                accountName: $account->accountName,
                currency: $ledger->currency,
                debitBalance: $debitBalance,
                creditBalance: $creditBalance,
            );
        }

        return TrialBalance::create(
            id: (string) Ulid::generate(),
            ledgerId: $ledgerId,
            periodId: 'ASOF',
            asOfDate: $asOfDate,
            lines: $lines,
        );
    }

    /**
     * Get trial balance summary
     * 
     * @param string $ledgerId Ledger ULID
     * @param string $periodId Period ULID
     * @return array Summary data
     */
    public function getTrialBalanceSummary(string $ledgerId, string $periodId): array
    {
        $trialBalance = $this->generateTrialBalance($ledgerId, $periodId);
        return $trialBalance->getSummary();
    }

    /**
     * Get accounts with unusual balances
     * 
     * Useful for debugging - returns accounts where debit + credit doesn't make sense.
     * 
     * @param string $ledgerId Ledger ULID
     * @param string $periodId Period ULID
     * @return array Accounts with unusual activity
     */
    public function getAccountsWithUnusualActivity(
        string $ledgerId,
        string $periodId,
    ): array {
        $accounts = $this->accountQuery->findByLedger($ledgerId);
        $unusualAccounts = [];

        foreach ($accounts as $account) {
            $totals = $this->balanceService->getAccountTotals($account->id, $periodId);
            
            // Check if there's activity
            $hasActivity = !$totals['total_debits']->isZero() 
                || !$totals['total_credits']->isZero();
            
            if (!$hasActivity) {
                continue;
            }

            // Check if totals are significantly different (potential error)
            $difference = $totals['total_debits']->amount->subtract(
                $totals['total_credits']->amount
            );

            // If difference is more than 1% of total activity, flag it
            $totalActivity = $totals['total_debits']->amount->add(
                $totals['total_credits']->amount
            );

            if (!$totalActivity->isZero()) {
                $threshold = $totalActivity->multiply(0.01);
                if ($difference->abs()->compareTo($threshold) > 0) {
                    $unusualAccounts[] = [
                        'account_id' => $account->id,
                        'account_code' => $account->accountCode,
                        'account_name' => $account->accountName,
                        'total_debits' => $totals['total_debits']->amount->getAmount(),
                        'total_credits' => $totals['total_credits']->amount->getAmount(),
                        'difference' => $difference->getAmount(),
                    ];
                }
            }
        }

        return $unusualAccounts;
    }
}
