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

        // Calculate totals from account balances
        $totalAssets = '0.00';
        $totalLiabilities = '0.00';
        $totalEquity = '0.00';

        foreach ($balances as $balance) {
            // This is simplified - real implementation would check account type
            // by fetching account details from ChartOfAccount package
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

        return new IncomeStatementDTO(
            id: uniqid('is_'),
            tenantId: $tenantId,
            periodId: $periodId,
            framework: $framework,
            sections: [],
            totalRevenue: '0.00',
            totalExpenses: '0.00',
            netIncome: '0.00',
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

        return new CashFlowStatementDTO(
            id: uniqid('cf_'),
            tenantId: $tenantId,
            periodId: $periodId,
            framework: $framework,
            sections: [],
            operatingCashFlow: '0.00',
            investingCashFlow: '0.00',
            financingCashFlow: '0.00',
            netCashChange: '0.00',
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

        return new StatementOfChangesInEquityDTO(
            id: uniqid('sce_'),
            tenantId: $tenantId,
            periodId: $periodId,
            framework: $framework,
            sections: [],
            openingEquity: '0.00',
            closingEquity: '0.00',
            generatedAt: new \DateTimeImmutable(),
        );
    }
}
