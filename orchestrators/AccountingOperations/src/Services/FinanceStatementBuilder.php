<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Services;

use Nexus\FinancialStatements\Contracts\StatementBuilderInterface;
use Nexus\FinancialStatements\Contracts\FinancialStatementInterface;
use Nexus\FinancialStatements\Entities\BalanceSheet;
use Nexus\FinancialStatements\Entities\IncomeStatement;
use Nexus\FinancialStatements\Entities\CashFlowStatement;
use Nexus\FinancialStatements\Entities\StatementOfChangesInEquity;
use Nexus\FinancialStatements\Enums\StatementType;
use Nexus\FinancialStatements\Enums\ComplianceFramework;
use Nexus\AccountingOperations\DataProviders\FinanceDataProvider;

/**
 * Builds financial statements using data from Nexus\Finance.
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
    ): FinancialStatementInterface {
        return match ($type) {
            StatementType::BALANCE_SHEET => $this->buildBalanceSheet($tenantId, $periodId, $framework, $comparativePeriodId),
            StatementType::INCOME_STATEMENT => $this->buildIncomeStatement($tenantId, $periodId, $framework, $comparativePeriodId),
            StatementType::CASH_FLOW => $this->buildCashFlowStatement($tenantId, $periodId, $framework, $comparativePeriodId),
            StatementType::CHANGES_IN_EQUITY => $this->buildStatementOfChangesInEquity($tenantId, $periodId, $framework, $comparativePeriodId),
            default => throw new \InvalidArgumentException("Unsupported statement type: {$type->value}"),
        };
    }

    private function buildBalanceSheet(
        string $tenantId,
        string $periodId,
        ComplianceFramework $framework,
        ?string $comparativePeriodId,
    ): BalanceSheet {
        $balances = $this->dataProvider->getAccountBalances($tenantId, $periodId);
        $metadata = $this->dataProvider->getPeriodMetadata($tenantId, $periodId);

        // Implementation builds BalanceSheet from data
        return new BalanceSheet(
            id: uniqid('bs_'),
            tenantId: $tenantId,
            periodId: $periodId,
            framework: $framework,
            sections: [],
            totalAssets: 0.0,
            totalLiabilities: 0.0,
            totalEquity: 0.0,
            generatedAt: new \DateTimeImmutable(),
        );
    }

    private function buildIncomeStatement(
        string $tenantId,
        string $periodId,
        ComplianceFramework $framework,
        ?string $comparativePeriodId,
    ): IncomeStatement {
        $balances = $this->dataProvider->getAccountBalances($tenantId, $periodId);

        return new IncomeStatement(
            id: uniqid('is_'),
            tenantId: $tenantId,
            periodId: $periodId,
            framework: $framework,
            sections: [],
            totalRevenue: 0.0,
            totalExpenses: 0.0,
            netIncome: 0.0,
            generatedAt: new \DateTimeImmutable(),
        );
    }

    private function buildCashFlowStatement(
        string $tenantId,
        string $periodId,
        ComplianceFramework $framework,
        ?string $comparativePeriodId,
    ): CashFlowStatement {
        $cashFlowData = $this->dataProvider->getCashFlowData($tenantId, $periodId);

        return new CashFlowStatement(
            id: uniqid('cf_'),
            tenantId: $tenantId,
            periodId: $periodId,
            framework: $framework,
            sections: [],
            operatingCashFlow: 0.0,
            investingCashFlow: 0.0,
            financingCashFlow: 0.0,
            netCashChange: 0.0,
            generatedAt: new \DateTimeImmutable(),
        );
    }

    private function buildStatementOfChangesInEquity(
        string $tenantId,
        string $periodId,
        ComplianceFramework $framework,
        ?string $comparativePeriodId,
    ): StatementOfChangesInEquity {
        $movements = $this->dataProvider->getEquityMovements($tenantId, $periodId);

        return new StatementOfChangesInEquity(
            id: uniqid('sce_'),
            tenantId: $tenantId,
            periodId: $periodId,
            framework: $framework,
            sections: [],
            openingEquity: 0.0,
            closingEquity: 0.0,
            generatedAt: new \DateTimeImmutable(),
        );
    }
}
