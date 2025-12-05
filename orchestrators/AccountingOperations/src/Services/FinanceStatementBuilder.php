<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Services;

use Nexus\AccountingOperations\Contracts\StatementBuilderInterface;
use Nexus\AccountingOperations\DataProviders\FinanceDataProvider;
use Nexus\AccountingOperations\DTOs\BalanceSheetDTO;
use Nexus\AccountingOperations\DTOs\CashFlowStatementDTO;
use Nexus\AccountingOperations\DTOs\FinancialStatementDTO;
use Nexus\AccountingOperations\DTOs\IncomeStatementDTO;
use Nexus\AccountingOperations\DTOs\StatementOfChangesInEquityDTO;
use Nexus\AccountingOperations\Enums\ComplianceFramework;
use Nexus\AccountingOperations\Enums\StatementType;
use Nexus\ChartOfAccount\Enums\AccountType;

/**
 * Builds financial statements using data from ChartOfAccount and JournalEntry packages.
 *
 * Orchestrates data from:
 * - Nexus\ChartOfAccount - For account structure and balances
 * - Nexus\JournalEntry - For transaction data and movements
 */
final readonly class FinanceStatementBuilder implements StatementBuilderInterface
{
    public function __construct(
        private FinanceDataProvider $dataProvider,
    ) {}

    public function build(
        string $tenantId,
        string $periodId,
        StatementType $type,
        ComplianceFramework $framework,
        ?string $comparativePeriodId = null,
    ): FinancialStatementDTO {
        return match ($type) {
            StatementType::BALANCE_SHEET => $this->buildBalanceSheet($tenantId, $periodId, $framework, $comparativePeriodId),
            StatementType::INCOME_STATEMENT => $this->buildIncomeStatement($tenantId, $periodId, $framework, $comparativePeriodId),
            StatementType::CASH_FLOW => $this->buildCashFlowStatement($tenantId, $periodId, $framework, $comparativePeriodId),
            StatementType::CHANGES_IN_EQUITY => $this->buildStatementOfChangesInEquity($tenantId, $periodId, $framework, $comparativePeriodId),
        };
    }

    private function buildBalanceSheet(
        string $tenantId,
        string $periodId,
        ComplianceFramework $framework,
        ?string $comparativePeriodId,
    ): BalanceSheetDTO {
        $balances = $this->dataProvider->getAccountBalances($tenantId, $periodId);
        $metadata = $this->dataProvider->getPeriodMetadata($tenantId, $periodId);

        // Calculate totals from account balances using bcmath for precision
        $totalAssets = '0.00';
        $totalLiabilities = '0.00';
        $totalEquity = '0.00';

        foreach ($balances as $balance) {
            // Get the net balance for this account (debit - credit)
            $netBalance = $balance->getNetBalance();

            // Categorize by account type and accumulate using bcmath
            match ($balance->accountType) {
                AccountType::Asset => 
                    $totalAssets = bcadd($totalAssets, $netBalance, 2),
                AccountType::Liability => 
                    $totalLiabilities = bcadd($totalLiabilities, bcmul($netBalance, '-1', 2), 2),
                AccountType::Equity => 
                    $totalEquity = bcadd($totalEquity, bcmul($netBalance, '-1', 2), 2),
                default => null, // Revenue and Expense accounts don't appear on balance sheet
            };
        }

        return new BalanceSheetDTO(
            id: uniqid('bs_'),
            tenantId: $tenantId,
            periodId: $periodId,
            framework: $framework,
            sections: [],
            totalAssets: $totalAssets,
            totalLiabilities: $totalLiabilities,
            totalEquity: $totalEquity,
            generatedAt: new \DateTimeImmutable(),
        );
    }

    private function buildIncomeStatement(
        string $tenantId,
        string $periodId,
        ComplianceFramework $framework,
        ?string $comparativePeriodId,
    ): IncomeStatementDTO {
        $balances = $this->dataProvider->getAccountBalances($tenantId, $periodId);

        // Calculate totals from account balances using bcmath for precision
        $totalRevenue = '0.00';
        $totalExpenses = '0.00';

        foreach ($balances as $balance) {
            // Get the net balance for this account (debit - credit)
            $netBalance = $balance->getNetBalance();

            // Categorize by account type and accumulate using bcmath
            match ($balance->accountType) {
                AccountType::Revenue => 
                    // Revenue has credit normal balance, so we invert the net balance
                    $totalRevenue = bcadd($totalRevenue, bcmul($netBalance, '-1', 2), 2),
                AccountType::Expense => 
                    // Expense has debit normal balance
                    $totalExpenses = bcadd($totalExpenses, $netBalance, 2),
                default => null, // Balance sheet accounts don't appear on income statement
            };
        }

        // Calculate net income: Revenue - Expenses
        $netIncome = bcsub($totalRevenue, $totalExpenses, 2);

        return new IncomeStatementDTO(
            id: uniqid('is_'),
            tenantId: $tenantId,
            periodId: $periodId,
            framework: $framework,
            sections: [],
            totalRevenue: $totalRevenue,
            totalExpenses: $totalExpenses,
            netIncome: $netIncome,
            generatedAt: new \DateTimeImmutable(),
        );
    }

    private function buildCashFlowStatement(
        string $tenantId,
        string $periodId,
        ComplianceFramework $framework,
        ?string $comparativePeriodId,
    ): CashFlowStatementDTO {
        $cashFlowData = $this->dataProvider->getCashFlowData($tenantId, $periodId);

        // TODO: Update getCashFlowData to return string values directly
        // For now, convert to string using number_format for precision up to 2 decimal places
        $operatingCashFlow = number_format($cashFlowData['operating_activities'], 2, '.', '');
        $investingCashFlow = number_format($cashFlowData['investing_activities'], 2, '.', '');
        $financingCashFlow = number_format($cashFlowData['financing_activities'], 2, '.', '');

        // Calculate net cash change using bcmath for precision
        $netCashChange = bcadd(
            bcadd($operatingCashFlow, $investingCashFlow, 2),
            $financingCashFlow,
            2
        );

        return new CashFlowStatementDTO(
            id: uniqid('cf_'),
            tenantId: $tenantId,
            periodId: $periodId,
            framework: $framework,
            sections: [],
            operatingCashFlow: $operatingCashFlow,
            investingCashFlow: $investingCashFlow,
            financingCashFlow: $financingCashFlow,
            netCashChange: $netCashChange,
            generatedAt: new \DateTimeImmutable(),
        );
    }

    private function buildStatementOfChangesInEquity(
        string $tenantId,
        string $periodId,
        ComplianceFramework $framework,
        ?string $comparativePeriodId,
    ): StatementOfChangesInEquityDTO {
        $movements = $this->dataProvider->getEquityMovements($tenantId, $periodId);

        // TODO: Update getEquityMovements to return string values directly
        // For now, convert to string using number_format for precision up to 2 decimal places
        $beginningBalance = number_format($movements['beginning_balance'], 2, '.', '');
        $netIncome = number_format($movements['net_income'], 2, '.', '');
        $dividends = number_format($movements['dividends'], 2, '.', '');
        $otherAdjustments = number_format($movements['other_adjustments'], 2, '.', '');

        // Calculate ending balance using bcmath for precision
        // Ending = Beginning + Net Income - Dividends + Other Adjustments
        $endingBalance = bcadd(
            bcsub(
                bcadd($beginningBalance, $netIncome, 2),
                $dividends,
                2
            ),
            $otherAdjustments,
            2
        );

        return new StatementOfChangesInEquityDTO(
            id: uniqid('sce_'),
            tenantId: $tenantId,
            periodId: $periodId,
            framework: $framework,
            sections: [],
            openingEquity: $beginningBalance,
            closingEquity: $endingBalance,
            generatedAt: new \DateTimeImmutable(),
        );
    }
}
