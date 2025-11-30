<?php

declare(strict_types=1);

namespace Nexus\Accounting\Core\Engine;

use Nexus\Accounting\Contracts\{
    StatementBuilderInterface,
    BalanceSheetInterface,
    IncomeStatementInterface,
    CashFlowStatementInterface
};
use Nexus\Accounting\Core\ValueObjects\{
    ReportingPeriod,
    StatementSection,
    StatementLineItem
};
use Nexus\Accounting\Core\Enums\{StatementType, CashFlowMethod};
use Nexus\Accounting\Core\Engine\Models\{BalanceSheet, IncomeStatement, CashFlowStatement};
use Nexus\Accounting\Exceptions\StatementGenerationException;
use Nexus\Finance\Contracts\LedgerRepositoryInterface;
use Nexus\Period\Contracts\PeriodManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Statement generation engine.
 *
 * Builds financial statements from ledger data.
 */
final readonly class StatementBuilder implements StatementBuilderInterface
{
    public function __construct(
        private LedgerRepositoryInterface $ledgerRepository,
        private PeriodManagerInterface $periodManager,
        private LoggerInterface $logger
    ) {}

    /**
     * {@inheritdoc}
     */
    public function buildBalanceSheet(
        string $entityId,
        ReportingPeriod $period,
        array $options = []
    ): BalanceSheetInterface {
        $this->logger->info('Building balance sheet', [
            'entity_id' => $entityId,
            'period' => $period->getLabel(),
        ]);

        try {
            // Get account balances at period end
            $balances = $this->ledgerRepository->getAccountBalances(
                $entityId,
                $period->getEndDate()
            );

            // Group accounts by section
            $assetSection = $this->buildAssetSection($balances, $options);
            $liabilitySection = $this->buildLiabilitySection($balances, $options);
            $equitySection = $this->buildEquitySection($balances, $options);

            $sections = [
                $assetSection,
                $liabilitySection,
                $equitySection,
            ];

            return new BalanceSheet(
                entityId: $entityId,
                period: $period,
                sections: $sections,
                metadata: $this->generateMetadata(StatementType::BALANCE_SHEET, $options)
            );

        } catch (\Throwable $e) {
            throw StatementGenerationException::ledgerError('Balance Sheet', $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildIncomeStatement(
        string $entityId,
        ReportingPeriod $period,
        array $options = []
    ): IncomeStatementInterface {
        $this->logger->info('Building income statement', [
            'entity_id' => $entityId,
            'period' => $period->getLabel(),
        ]);

        try {
            // Get account activity for the period
            $activity = $this->ledgerRepository->getAccountActivity(
                $entityId,
                $period->getStartDate(),
                $period->getEndDate()
            );

            // Group accounts by section
            $revenueSection = $this->buildRevenueSection($activity, $options);
            $cogsSection = $this->buildCOGSSection($activity, $options);
            $expenseSection = $this->buildExpenseSection($activity, $options);
            $otherSection = $this->buildOtherIncomeSection($activity, $options);

            $sections = [
                $revenueSection,
                $cogsSection,
                $expenseSection,
                $otherSection,
            ];

            return new IncomeStatement(
                entityId: $entityId,
                period: $period,
                sections: $sections,
                metadata: $this->generateMetadata(StatementType::INCOME_STATEMENT, $options)
            );

        } catch (\Throwable $e) {
            throw StatementGenerationException::ledgerError('Income Statement', $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildCashFlowStatement(
        string $entityId,
        ReportingPeriod $period,
        CashFlowMethod $method,
        array $options = []
    ): CashFlowStatementInterface {
        $this->logger->info('Building cash flow statement', [
            'entity_id' => $entityId,
            'period' => $period->getLabel(),
            'method' => $method->value,
        ]);

        try {
            if ($method === CashFlowMethod::INDIRECT) {
                return $this->buildIndirectCashFlow($entityId, $period, $options);
            }

            return $this->buildDirectCashFlow($entityId, $period, $options);

        } catch (\Throwable $e) {
            throw StatementGenerationException::ledgerError('Cash Flow Statement', $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildComparativeStatement(
        string $entityId,
        array $periods,
        array $options = []
    ): array {
        $statements = [];
        $statementType = $options['type'] ?? StatementType::INCOME_STATEMENT;

        foreach ($periods as $period) {
            $statements[] = match($statementType) {
                StatementType::BALANCE_SHEET => $this->buildBalanceSheet($entityId, $period, $options),
                StatementType::INCOME_STATEMENT => $this->buildIncomeStatement($entityId, $period, $options),
                StatementType::CASH_FLOW => $this->buildCashFlowStatement(
                    $entityId,
                    $period,
                    $options['method'] ?? CashFlowMethod::INDIRECT,
                    $options
                ),
                default => throw StatementGenerationException::forType(
                    $statementType->value,
                    'Comparative statements not supported for this type'
                ),
            };
        }

        return $statements;
    }

    /**
     * Build asset section.
     */
    private function buildAssetSection(array $balances, array $options): StatementSection
    {
        $lineItems = [];
        $currentAssets = [];
        $nonCurrentAssets = [];

        foreach ($balances as $account) {
            if ($account['type'] !== 'asset') {
                continue;
            }

            $item = new StatementLineItem(
                code: $account['code'],
                label: $account['name'],
                amount: $account['balance'],
                level: $account['level'],
                parentCode: $account['parent_code'] ?? null,
                isBold: $account['is_header'] ?? false,
                isTotal: $account['is_total'] ?? false
            );

            if ($account['is_current'] ?? false) {
                $currentAssets[] = $item;
            } else {
                $nonCurrentAssets[] = $item;
            }
        }

        // Combine and sort
        $lineItems = array_merge($currentAssets, $nonCurrentAssets);

        return new StatementSection(
            code: 'ASSETS',
            name: 'Assets',
            lineItems: $lineItems,
            order: 1
        );
    }

    /**
     * Build liability section.
     */
    private function buildLiabilitySection(array $balances, array $options): StatementSection
    {
        $lineItems = [];

        foreach ($balances as $account) {
            if ($account['type'] !== 'liability') {
                continue;
            }

            $lineItems[] = new StatementLineItem(
                code: $account['code'],
                label: $account['name'],
                amount: $account['balance'],
                level: $account['level'],
                parentCode: $account['parent_code'] ?? null,
                isBold: $account['is_header'] ?? false,
                isTotal: $account['is_total'] ?? false
            );
        }

        return new StatementSection(
            code: 'LIABILITIES',
            name: 'Liabilities',
            lineItems: $lineItems,
            order: 2
        );
    }

    /**
     * Build equity section.
     */
    private function buildEquitySection(array $balances, array $options): StatementSection
    {
        $lineItems = [];

        foreach ($balances as $account) {
            if ($account['type'] !== 'equity') {
                continue;
            }

            $lineItems[] = new StatementLineItem(
                code: $account['code'],
                label: $account['name'],
                amount: $account['balance'],
                level: $account['level'],
                parentCode: $account['parent_code'] ?? null,
                isBold: $account['is_header'] ?? false,
                isTotal: $account['is_total'] ?? false
            );
        }

        return new StatementSection(
            code: 'EQUITY',
            name: 'Equity',
            lineItems: $lineItems,
            order: 3
        );
    }

    /**
     * Build revenue section.
     */
    private function buildRevenueSection(array $activity, array $options): StatementSection
    {
        $lineItems = [];

        foreach ($activity as $account) {
            if ($account['type'] !== 'revenue') {
                continue;
            }

            $lineItems[] = new StatementLineItem(
                code: $account['code'],
                label: $account['name'],
                amount: $account['net_change'],
                level: $account['level'],
                parentCode: $account['parent_code'] ?? null,
                isBold: $account['is_header'] ?? false,
                isTotal: $account['is_total'] ?? false
            );
        }

        return new StatementSection(
            code: 'REVENUE',
            name: 'Revenue',
            lineItems: $lineItems,
            order: 1
        );
    }

    /**
     * Build COGS section.
     */
    private function buildCOGSSection(array $activity, array $options): StatementSection
    {
        $lineItems = [];

        foreach ($activity as $account) {
            if ($account['type'] !== 'cogs') {
                continue;
            }

            $lineItems[] = new StatementLineItem(
                code: $account['code'],
                label: $account['name'],
                amount: $account['net_change'],
                level: $account['level'],
                parentCode: $account['parent_code'] ?? null,
                isBold: $account['is_header'] ?? false,
                isTotal: $account['is_total'] ?? false
            );
        }

        return new StatementSection(
            code: 'COGS',
            name: 'Cost of Goods Sold',
            lineItems: $lineItems,
            order: 2
        );
    }

    /**
     * Build expense section.
     */
    private function buildExpenseSection(array $activity, array $options): StatementSection
    {
        $lineItems = [];

        foreach ($activity as $account) {
            if ($account['type'] !== 'expense') {
                continue;
            }

            $lineItems[] = new StatementLineItem(
                code: $account['code'],
                label: $account['name'],
                amount: $account['net_change'],
                level: $account['level'],
                parentCode: $account['parent_code'] ?? null,
                isBold: $account['is_header'] ?? false,
                isTotal: $account['is_total'] ?? false
            );
        }

        return new StatementSection(
            code: 'EXPENSES',
            name: 'Operating Expenses',
            lineItems: $lineItems,
            order: 3
        );
    }

    /**
     * Build other income section.
     */
    private function buildOtherIncomeSection(array $activity, array $options): StatementSection
    {
        $lineItems = [];

        foreach ($activity as $account) {
            if ($account['type'] !== 'other_income' && $account['type'] !== 'other_expense') {
                continue;
            }

            $lineItems[] = new StatementLineItem(
                code: $account['code'],
                label: $account['name'],
                amount: $account['net_change'],
                level: $account['level'],
                parentCode: $account['parent_code'] ?? null,
                isBold: $account['is_header'] ?? false,
                isTotal: $account['is_total'] ?? false
            );
        }

        return new StatementSection(
            code: 'OTHER',
            name: 'Other Income/Expenses',
            lineItems: $lineItems,
            order: 4
        );
    }

    /**
     * Build indirect method cash flow.
     */
    private function buildIndirectCashFlow(
        string $entityId,
        ReportingPeriod $period,
        array $options
    ): CashFlowStatementInterface {
        // Start with net income from income statement
        $incomeStatement = $this->buildIncomeStatement($entityId, $period, $options);
        $netIncome = $incomeStatement->getNetIncome();

        // Get cash flow data
        $cashFlowData = $this->ledgerRepository->getCashFlowData(
            $entityId,
            $period->getStartDate(),
            $period->getEndDate()
        );

        $operatingSection = $this->buildOperatingCashFlow($netIncome, $cashFlowData, $options);
        $investingSection = $this->buildInvestingCashFlow($cashFlowData, $options);
        $financingSection = $this->buildFinancingCashFlow($cashFlowData, $options);

        $sections = [
            $operatingSection,
            $investingSection,
            $financingSection,
        ];

        return new CashFlowStatement(
            entityId: $entityId,
            period: $period,
            method: CashFlowMethod::INDIRECT,
            sections: $sections,
            beginningCash: $cashFlowData['beginning_cash'] ?? 0.0,
            endingCash: $cashFlowData['ending_cash'] ?? 0.0,
            metadata: $this->generateMetadata(StatementType::CASH_FLOW, $options)
        );
    }

    /**
     * Build direct method cash flow.
     */
    private function buildDirectCashFlow(
        string $entityId,
        ReportingPeriod $period,
        array $options
    ): CashFlowStatementInterface {
        // Get detailed cash transactions
        $cashTransactions = $this->ledgerRepository->getCashTransactions(
            $entityId,
            $period->getStartDate(),
            $period->getEndDate()
        );

        $operatingSection = $this->buildDirectOperatingCashFlow($cashTransactions, $options);
        $investingSection = $this->buildInvestingCashFlow($cashTransactions, $options);
        $financingSection = $this->buildFinancingCashFlow($cashTransactions, $options);

        $sections = [
            $operatingSection,
            $investingSection,
            $financingSection,
        ];

        return new CashFlowStatement(
            entityId: $entityId,
            period: $period,
            method: CashFlowMethod::DIRECT,
            sections: $sections,
            beginningCash: $cashTransactions['beginning_cash'] ?? 0.0,
            endingCash: $cashTransactions['ending_cash'] ?? 0.0,
            metadata: $this->generateMetadata(StatementType::CASH_FLOW, $options)
        );
    }

    /**
     * Build operating cash flow (indirect).
     */
    private function buildOperatingCashFlow(float $netIncome, array $data, array $options): StatementSection
    {
        $lineItems = [
            new StatementLineItem(
                code: 'NET_INCOME',
                label: 'Net Income',
                amount: $netIncome,
                level: 0,
                isBold: true
            ),
        ];

        // Add back non-cash expenses
        foreach ($data['adjustments'] ?? [] as $adjustment) {
            $lineItems[] = new StatementLineItem(
                code: $adjustment['code'],
                label: $adjustment['label'],
                amount: $adjustment['amount'],
                level: 1
            );
        }

        return new StatementSection(
            code: 'OPERATING',
            name: 'Cash from Operating Activities',
            lineItems: $lineItems,
            order: 1
        );
    }

    /**
     * Build direct operating cash flow.
     */
    private function buildDirectOperatingCashFlow(array $transactions, array $options): StatementSection
    {
        $lineItems = [];

        foreach ($transactions['operating'] ?? [] as $transaction) {
            $lineItems[] = new StatementLineItem(
                code: $transaction['code'],
                label: $transaction['label'],
                amount: $transaction['amount'],
                level: 0
            );
        }

        return new StatementSection(
            code: 'OPERATING',
            name: 'Cash from Operating Activities',
            lineItems: $lineItems,
            order: 1
        );
    }

    /**
     * Build investing cash flow.
     */
    private function buildInvestingCashFlow(array $data, array $options): StatementSection
    {
        $lineItems = [];

        foreach ($data['investing'] ?? [] as $item) {
            $lineItems[] = new StatementLineItem(
                code: $item['code'],
                label: $item['label'],
                amount: $item['amount'],
                level: 0
            );
        }

        return new StatementSection(
            code: 'INVESTING',
            name: 'Cash from Investing Activities',
            lineItems: $lineItems,
            order: 2
        );
    }

    /**
     * Build financing cash flow.
     */
    private function buildFinancingCashFlow(array $data, array $options): StatementSection
    {
        $lineItems = [];

        foreach ($data['financing'] ?? [] as $item) {
            $lineItems[] = new StatementLineItem(
                code: $item['code'],
                label: $item['label'],
                amount: $item['amount'],
                level: 0
            );
        }

        return new StatementSection(
            code: 'FINANCING',
            name: 'Cash from Financing Activities',
            lineItems: $lineItems,
            order: 3
        );
    }

    /**
     * Generate statement metadata.
     *
     * @return array<string, mixed>
     */
    private function generateMetadata(StatementType $type, array $options): array
    {
        return [
            'generated_at' => new \DateTimeImmutable(),
            'statement_type' => $type->value,
            'version' => 1,
            'options' => $options,
        ];
    }
}
