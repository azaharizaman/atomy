<?php

declare(strict_types=1);

namespace Nexus\Statutory\Core\Engine;

use Psr\Log\LoggerInterface;
use Nexus\Statutory\Exceptions\DataExtractionException;

/**
 * Finance data extractor for statutory reports.
 * 
 * Extracts financial data from the finance package for statutory reporting.
 * This is a framework-agnostic component that defines interfaces for data extraction.
 */
final class FinanceDataExtractor
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Extract profit & loss data for a period.
     *
     * @param string $tenantId The tenant identifier
     * @param \DateTimeImmutable $startDate Period start date
     * @param \DateTimeImmutable $endDate Period end date
     * @param array<string, mixed> $accountData Account data from repository
     * @return array<string, mixed> P&L data structure
     */
    public function extractProfitLoss(
        string $tenantId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        array $accountData
    ): array {
        $this->logger->info("Extracting P&L data", [
            'tenant_id' => $tenantId,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]);

        // Aggregate revenue accounts (type: Revenue)
        $revenue = $this->aggregateAccountsByType($accountData, 'Revenue');

        // Aggregate expense accounts (type: Expense)
        $expenses = $this->aggregateAccountsByType($accountData, 'Expense');

        // Calculate net income
        $netIncome = $revenue - $expenses;

        $result = [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'net_income' => $netIncome,
            'period_start' => $startDate->format('Y-m-d'),
            'period_end' => $endDate->format('Y-m-d'),
        ];

        $this->logger->info("P&L data extracted", [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'net_income' => $netIncome,
        ]);

        return $result;
    }

    /**
     * Extract balance sheet data for a specific date.
     *
     * @param string $tenantId The tenant identifier
     * @param \DateTimeImmutable $asOfDate The date for balance sheet
     * @param array<string, mixed> $accountData Account data from repository
     * @return array<string, mixed> Balance sheet data structure
     */
    public function extractBalanceSheet(
        string $tenantId,
        \DateTimeImmutable $asOfDate,
        array $accountData
    ): array {
        $this->logger->info("Extracting balance sheet data", [
            'tenant_id' => $tenantId,
            'as_of_date' => $asOfDate->format('Y-m-d'),
        ]);

        // Aggregate asset accounts (type: Asset)
        $assets = $this->aggregateAccountsByType($accountData, 'Asset');

        // Aggregate liability accounts (type: Liability)
        $liabilities = $this->aggregateAccountsByType($accountData, 'Liability');

        // Aggregate equity accounts (type: Equity)
        $equity = $this->aggregateAccountsByType($accountData, 'Equity');

        $result = [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'as_of_date' => $asOfDate->format('Y-m-d'),
        ];

        $this->logger->info("Balance sheet data extracted", [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
        ]);

        return $result;
    }

    /**
     * Extract trial balance data for a period.
     *
     * @param string $tenantId The tenant identifier
     * @param \DateTimeImmutable $startDate Period start date
     * @param \DateTimeImmutable $endDate Period end date
     * @param array<string, mixed> $accountData Account data from repository
     * @return array<array<string, mixed>> Trial balance data (array of account rows)
     */
    public function extractTrialBalance(
        string $tenantId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        array $accountData
    ): array {
        $this->logger->info("Extracting trial balance data", [
            'tenant_id' => $tenantId,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]);

        $trialBalance = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($accountData as $account) {
            $balance = $account['balance'] ?? 0.0;
            $debit = $balance >= 0 ? $balance : 0.0;
            $credit = $balance < 0 ? abs($balance) : 0.0;

            $trialBalance[] = [
                'account_code' => $account['code'] ?? 'N/A',
                'account_name' => $account['name'] ?? 'Unknown',
                'debit' => $debit,
                'credit' => $credit,
            ];

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        // Add totals row
        $trialBalance[] = [
            'account_code' => 'TOTAL',
            'account_name' => 'Total',
            'debit' => $totalDebit,
            'credit' => $totalCredit,
        ];

        $this->logger->info("Trial balance data extracted", [
            'account_count' => count($accountData),
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
        ]);

        return $trialBalance;
    }

    /**
     * Aggregate account balances by account type.
     *
     * @param array<string, mixed> $accountData Account data
     * @param string $accountType The account type to filter by
     * @return float The aggregated balance
     */
    private function aggregateAccountsByType(array $accountData, string $accountType): float
    {
        $total = 0.0;

        foreach ($accountData as $account) {
            if (($account['type'] ?? null) === $accountType) {
                $total += $account['balance'] ?? 0.0;
            }
        }

        return $total;
    }
}
