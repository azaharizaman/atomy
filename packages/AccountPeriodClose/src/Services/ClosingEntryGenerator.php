<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\Services;

use Nexus\AccountPeriodClose\Contracts\ClosingEntryGeneratorInterface;
use Nexus\AccountPeriodClose\ValueObjects\ClosingEntrySpec;

/**
 * Pure logic for generating closing entries.
 */
final readonly class ClosingEntryGenerator implements ClosingEntryGeneratorInterface
{
    private const string INCOME_SUMMARY_ACCOUNT = '3100';

    public function generate(
        string $periodId,
        array $incomeStatementBalances,
        string $retainedEarningsAccount
    ): array {
        $entries = [];
        $entryDate = new \DateTimeImmutable();

        $revenueTotal = 0.0;
        $expenseTotal = 0.0;

        foreach ($incomeStatementBalances as $accountCode => $balance) {
            if ($this->isRevenueAccount($accountCode)) {
                $revenueTotal += $balance;
                $entries[] = new ClosingEntrySpec(
                    description: "Close revenue account {$accountCode}",
                    debitAccountCode: $accountCode,
                    creditAccountCode: self::INCOME_SUMMARY_ACCOUNT,
                    amount: $balance,
                    entryDate: $entryDate,
                    reference: "CLOSE-{$periodId}"
                );
            } elseif ($this->isExpenseAccount($accountCode)) {
                $expenseTotal += $balance;
                $entries[] = new ClosingEntrySpec(
                    description: "Close expense account {$accountCode}",
                    debitAccountCode: self::INCOME_SUMMARY_ACCOUNT,
                    creditAccountCode: $accountCode,
                    amount: $balance,
                    entryDate: $entryDate,
                    reference: "CLOSE-{$periodId}"
                );
            }
        }

        $netIncome = $revenueTotal - $expenseTotal;
        if ($netIncome != 0) {
            if ($netIncome > 0) {
                $entries[] = new ClosingEntrySpec(
                    description: "Transfer net income to retained earnings",
                    debitAccountCode: self::INCOME_SUMMARY_ACCOUNT,
                    creditAccountCode: $retainedEarningsAccount,
                    amount: $netIncome,
                    entryDate: $entryDate,
                    reference: "CLOSE-{$periodId}"
                );
            } else {
                $entries[] = new ClosingEntrySpec(
                    description: "Transfer net loss to retained earnings",
                    debitAccountCode: $retainedEarningsAccount,
                    creditAccountCode: self::INCOME_SUMMARY_ACCOUNT,
                    amount: abs($netIncome),
                    entryDate: $entryDate,
                    reference: "CLOSE-{$periodId}"
                );
            }
        }

        return $entries;
    }

    public function generateYearEnd(string $periodId, array $balances): array
    {
        return $this->generate($periodId, $balances, '3200');
    }

    private function isRevenueAccount(string $accountCode): bool
    {
        return str_starts_with($accountCode, '4');
    }

    private function isExpenseAccount(string $accountCode): bool
    {
        return str_starts_with($accountCode, '5') || str_starts_with($accountCode, '6');
    }
}
