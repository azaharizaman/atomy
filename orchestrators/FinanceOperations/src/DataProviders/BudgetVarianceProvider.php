<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DataProviders;

use Nexus\FinanceOperations\Contracts\BudgetVarianceProviderInterface;
use Nexus\FinanceOperations\Contracts\BudgetQueryInterface;
use Nexus\FinanceOperations\Contracts\LedgerQueryInterface;
use Nexus\FinanceOperations\Contracts\CostQueryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Data provider for budget variance data aggregation.
 *
 * Aggregates data from:
 * - Budget package (budget amounts, versions)
 * - JournalEntry package (actual amounts from GL)
 * - CostAccounting package (committed costs)
 *
 * Following Advanced Orchestrator Pattern v1.1:
 * DataProviders abstract data fetching from Coordinators.
 *
 * @since 1.0.0
 */
final readonly class BudgetVarianceProvider implements BudgetVarianceProviderInterface
{
    public function __construct(
        private BudgetQueryInterface $budgetQuery,
        private LedgerQueryInterface $glQuery,
        private ?CostQueryInterface $costQuery = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * @inheritDoc
     */
    public function getBudgetData(string $tenantId, string $periodId, ?string $budgetVersionId = null): array
    {
        $this->logger->debug('Fetching budget data', [
            'tenant_id' => $tenantId,
            'period_id' => $periodId,
            'budget_version_id' => $budgetVersionId,
        ]);

        try {
            $budgets = $this->budgetQuery->getBudgetsByPeriod($tenantId, $periodId, $budgetVersionId);
            $result = [];

            foreach ($budgets as $budget) {
                $result[] = [
                    'budget_id' => $budget->getId(),
                    'name' => $budget->getName(),
                    'cost_center_id' => $budget->getCostCenterId(),
                    'cost_center_name' => $budget->getCostCenterName(),
                    'account_code' => $budget->getAccountCode(),
                    'account_name' => $budget->getAccountName(),
                    'budgeted_amount' => $budget->getAmount(),
                    'currency' => $budget->getCurrency(),
                    'version' => $budget->getVersion(),
                    'version_name' => $budget->getVersionName(),
                    'is_original' => $budget->isOriginal(),
                ];
            }

            return [
                'period_id' => $periodId,
                'budget_version_id' => $budgetVersionId,
                'budgets' => $result,
                'total_budgeted' => $this->calculateTotalBudget($result),
                'currency' => $this->getCommonCurrency($result),
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch budget data', [
                'tenant_id' => $tenantId,
                'period_id' => $periodId,
                'budget_version_id' => $budgetVersionId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function getActualData(string $tenantId, string $periodId): array
    {
        $this->logger->debug('Fetching actual data', [
            'tenant_id' => $tenantId,
            'period_id' => $periodId,
        ]);

        try {
            $actuals = $this->glQuery->getActualsByPeriod($tenantId, $periodId);
            $result = [];

            foreach ($actuals as $actual) {
                $result[] = [
                    'account_code' => $actual->getAccountCode(),
                    'account_name' => $actual->getAccountName(),
                    'cost_center_id' => $actual->getCostCenterId(),
                    'cost_center_name' => $actual->getCostCenterName(),
                    'actual_amount' => $actual->getBalance(),
                    'currency' => $actual->getCurrency(),
                    'debit_total' => $actual->getDebitTotal(),
                    'credit_total' => $actual->getCreditTotal(),
                ];
            }

            return [
                'period_id' => $periodId,
                'actuals' => $result,
                'total_actual' => $this->calculateTotalActual($result),
                'currency' => $this->getCommonCurrency($result),
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch actual data', [
                'tenant_id' => $tenantId,
                'period_id' => $periodId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function getVarianceAnalysis(string $tenantId, string $periodId, ?string $budgetVersionId = null): array
    {
        $this->logger->debug('Performing variance analysis', [
            'tenant_id' => $tenantId,
            'period_id' => $periodId,
            'budget_version_id' => $budgetVersionId,
        ]);

        try {
            $budgetData = $this->getBudgetData($tenantId, $periodId, $budgetVersionId);
            $actualData = $this->getActualData($tenantId, $periodId);

            // Create lookup for actuals by account code and cost center
            $actualsByKey = [];
            foreach ($actualData['actuals'] as $actual) {
                $key = $this->buildVarianceKey(
                    $actual['account_code'],
                    $actual['cost_center_id']
                );
                // Aggregate by summing instead of overwriting
                if (isset($actualsByKey[$key])) {
                    $actualsByKey[$key] = bcadd($actualsByKey[$key], (string) $actual['actual_amount'], 2);
                } else {
                    $actualsByKey[$key] = (string) $actual['actual_amount'];
                }
            }

            $variances = [];
            $totalBudgeted = '0';
            $totalActual = '0';
            $totalVariance = '0';
            $favorableCount = 0;
            $unfavorableCount = 0;

            foreach ($budgetData['budgets'] as $budget) {
                $key = $this->buildVarianceKey(
                    $budget['account_code'],
                    $budget['cost_center_id']
                );

                $budgeted = $budget['budgeted_amount'];
                $actual = $actualsByKey[$key] ?? '0';
                $variance = bcsub($budgeted, $actual, 2);

                // Determine if favorable or unfavorable
                // For revenue accounts, positive variance is favorable
                // For expense accounts, negative variance is favorable
                $isFavorable = $this->isVarianceFavorable($variance, $budget['account_code']);

                if ($isFavorable) {
                    $favorableCount++;
                } else {
                    $unfavorableCount++;
                }

                $variances[] = [
                    'budget_id' => $budget['budget_id'],
                    'account_code' => $budget['account_code'],
                    'account_name' => $budget['account_name'],
                    'cost_center_id' => $budget['cost_center_id'],
                    'cost_center_name' => $budget['cost_center_name'],
                    'budgeted' => $budgeted,
                    'actual' => $actual,
                    'variance' => $variance,
                    'variance_percent' => bccomp($budgeted, '0', 2) !== 0
                        ? bcmul(bcdiv($variance, $budgeted, 4), '100', 2)
                        : '0',
                    'is_favorable' => $isFavorable,
                ];

                $totalBudgeted = bcadd($totalBudgeted, $budgeted, 2);
                $totalActual = bcadd($totalActual, $actual, 2);
                $totalVariance = bcadd($totalVariance, $variance, 2);
            }

            // Get committed costs if available
            $committedCosts = [];
            $totalCommitted = '0';
            if ($this->costQuery !== null) {
                try {
                    $committed = $this->costQuery->getCommittedCosts($tenantId, $periodId);
                    foreach ($committed as $item) {
                        $committedCosts[] = [
                            'reference' => $item->getReference(),
                            'description' => $item->getDescription(),
                            'amount' => $item->getAmount(),
                            'type' => $item->getType(),
                            'vendor_id' => $item->getVendorId(),
                        ];
                        $totalCommitted = bcadd($totalCommitted, $item->getAmount(), 2);
                    }
                } catch (\Throwable $e) {
                    $this->logger->warning('Failed to fetch committed costs', [
                        'tenant_id' => $tenantId,
                        'period_id' => $periodId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return [
                'period_id' => $periodId,
                'budget_version_id' => $budgetVersionId,
                'total_budgeted' => $totalBudgeted,
                'total_actual' => $totalActual,
                'total_variance' => $totalVariance,
                'total_committed' => $totalCommitted,
                'available_budget' => bcsub(bcsub($totalBudgeted, $totalActual, 2), $totalCommitted, 2),
                'variance_percent' => bccomp($totalBudgeted, '0', 2) !== 0
                    ? bcmul(bcdiv($totalVariance, $totalBudgeted, 4), '100', 2)
                    : '0',
                'favorable_count' => $favorableCount,
                'unfavorable_count' => $unfavorableCount,
                'variances' => $variances,
                'committed_costs' => $committedCosts,
                'analyzed_at' => date('Y-m-d H:i:s'),
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to perform variance analysis', [
                'tenant_id' => $tenantId,
                'period_id' => $periodId,
                'budget_version_id' => $budgetVersionId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Build a composite key for variance lookup.
     */
    private function buildVarianceKey(string $accountCode, ?string $costCenterId): string
    {
        return "{$accountCode}:{$costCenterId}";
    }

    /**
     * Calculate total budget from budget items.
     *
     * @param array<int, array<string, mixed>> $items
     */
    private function calculateTotalBudget(array $items): string
    {
        $total = '0';
        foreach ($items as $item) {
            $total = bcadd($total, (string) ($item['budgeted_amount'] ?? '0'), 2);
        }
        return $total;
    }

    /**
     * Calculate total actual from actual items.
     *
     * @param array<int, array<string, mixed>> $items
     */
    private function calculateTotalActual(array $items): string
    {
        $total = '0';
        foreach ($items as $item) {
            $total = bcadd($total, (string) ($item['actual_amount'] ?? '0'), 2);
        }
        return $total;
    }

    /**
     * Get common currency from items.
     *
     * @param array<int, array<string, mixed>> $items
     */
    private function getCommonCurrency(array $items): ?string
    {
        if (empty($items)) {
            return null;
        }

        $currencies = [];
        foreach ($items as $item) {
            $currency = $item['currency'] ?? null;
            if ($currency === null) {
                // Missing currency - treat as inconsistent
                return null;
            }
            $currencies[] = $currency;
        }

        // Check if all currencies are the same
        $firstCurrency = $currencies[0];
        foreach ($currencies as $currency) {
            if ($currency !== $firstCurrency) {
                // Inconsistent currencies
                return null;
            }
        }

        return $firstCurrency;
    }

    /**
     * Determine if variance is favorable based on account type.
     *
     * For revenue/income accounts (typically 4xxx):
     *   - Negative variance (budgeted - actual) is favorable
     *
     * For expense accounts (typically 5xxx-9xxx):
     *   - Positive variance (actual < budget) is favorable
     */
    private function isVarianceFavorable(string $variance, string $accountCode): bool
    {
        // Determine account type from account code prefix
        // This is a simplified approach - real implementation would use account type from COA
        $prefix = substr($accountCode, 0, 1);

        // Revenue/income accounts (typically start with 4)
        // For revenue, when actual > budget (negative variance), it's favorable
        if ($prefix === '4') {
            return bccomp($variance, '0', 2) <= 0;
        }

        // Expense accounts (typically start with 5-9)
        // For expenses, under-budget (positive variance) is favorable
        return bccomp($variance, '0', 2) >= 0;
    }
}
