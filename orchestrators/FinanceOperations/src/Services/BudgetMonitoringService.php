<?php
declare(strict_types=1);

namespace Nexus\FinanceOperations\Services;

use Nexus\FinanceOperations\Contracts\BudgetVarianceProviderInterface;
use Nexus\FinanceOperations\DTOs\BudgetTracking\BudgetCheckRequest;
use Nexus\FinanceOperations\DTOs\BudgetTracking\BudgetCheckResult;
use Nexus\FinanceOperations\DTOs\BudgetTracking\BudgetVarianceRequest;
use Nexus\FinanceOperations\DTOs\BudgetTracking\BudgetVarianceResult;
use Nexus\FinanceOperations\DTOs\BudgetTracking\BudgetThresholdRequest;
use Nexus\FinanceOperations\DTOs\BudgetTracking\BudgetThresholdResult;
use Nexus\FinanceOperations\Exceptions\BudgetTrackingException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for budget monitoring and variance analysis.
 * 
 * This service handles:
 * - Budget availability checks
 * - Variance calculations
 * - Threshold monitoring
 * 
 * Following Advanced Orchestrator Pattern v1.1:
 * Services handle the "heavy lifting" - calculations and cross-boundary logic.
 * 
 * @since 1.0.0
 */
final readonly class BudgetMonitoringService
{
    public function __construct(
        private BudgetVarianceProviderInterface $dataProvider,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Check budget availability.
     *
     * @param BudgetCheckRequest $request The budget check request parameters
     * @return BudgetCheckResult The availability check result
     * @throws BudgetTrackingException If check fails
     */
    public function checkAvailability(BudgetCheckRequest $request): BudgetCheckResult
    {
        $this->logger->info('Checking budget availability', [
            'tenant_id' => $request->tenantId,
            'budget_id' => $request->budgetId,
            'amount' => $request->amount,
        ]);

        try {
            // Get budget data
            $budgetData = $this->dataProvider->getBudgetData(
                $request->tenantId,
                $request->periodId ?? '',
                $request->budgetId
            );

            // Find the specific budget line
            $budgeted = '0';
            $actual = '0';
            $committed = '0';

            // Check if we have direct budget amounts
            if (isset($budgetData['budgeted'])) {
                $budgeted = $budgetData['budgeted'];
                $actual = $budgetData['actual'] ?? '0';
                $committed = $budgetData['committed'] ?? '0';
            } elseif (isset($budgetData['budgets'])) {
                // Search in budget lines (provider returns 'budgets' array)
                foreach ($budgetData['budgets'] as $line) {
                    $lineBudgetId = $line['budget_id'] ?? $line['id'] ?? null;
                    $lineCostCenter = $line['cost_center_id'] ?? null;
                    $lineAccount = $line['account_id'] ?? $line['account_code'] ?? null;

                    // Match by budget ID, cost center, or account
                    $matches = ($lineBudgetId === $request->budgetId) ||
                        ($request->costCenterId && $lineCostCenter === $request->costCenterId) ||
                        ($request->accountId && $lineAccount === $request->accountId);

                    if ($matches) {
                        $budgeted = $line['budgeted'] ?? $line['budgeted_amount'] ?? $line['amount'] ?? '0';
                        $actual = $line['actual'] ?? '0';
                        $committed = $line['committed'] ?? '0';
                        break;
                    }
                }
            } elseif (isset($budgetData['lines'])) {
                // Search in budget lines (legacy format)
                foreach ($budgetData['lines'] as $line) {
                    $lineBudgetId = $line['budget_id'] ?? $line['id'] ?? null;
                    $lineCostCenter = $line['cost_center_id'] ?? null;
                    $lineAccount = $line['account_id'] ?? null;

                    // Match by budget ID, cost center, or account
                    $matches = ($lineBudgetId === $request->budgetId) ||
                        ($request->costCenterId && $lineCostCenter === $request->costCenterId) ||
                        ($request->accountId && $lineAccount === $request->accountId);

                    if ($matches) {
                        $budgeted = $line['budgeted'] ?? $line['amount'] ?? '0';
                        $actual = $line['actual'] ?? '0';
                        $committed = $line['committed'] ?? '0';
                        break;
                    }
                }
            }

            // Calculate available amount
            $availableAmount = (string)((float)$budgeted - (float)$actual - (float)$committed);
            $utilizationPercent = (float)$budgeted > 0 
                ? round((((float)$actual + (float)$committed) / (float)$budgeted) * 100, 2)
                : 0.0;

            $isAvailable = (float)$request->amount <= (float)$availableAmount;
            $warning = null;

            if (!$isAvailable) {
                $warning = sprintf(
                    'Insufficient budget: requested %s, available %s',
                    $request->amount,
                    $availableAmount
                );
            } elseif ($utilizationPercent >= 80) {
                $warning = sprintf(
                    'Budget utilization at %.1f%%',
                    $utilizationPercent
                );
            }

            return new BudgetCheckResult(
                available: $isAvailable,
                budgetId: $request->budgetId,
                budgeted: $budgeted,
                actual: $actual,
                committed: $committed,
                availableAmount: $availableAmount,
                utilizationPercent: $utilizationPercent,
                warning: $warning,
            );
        } catch (\Throwable $e) {
            $this->logger->error('Budget availability check failed', [
                'tenant_id' => $request->tenantId,
                'budget_id' => $request->budgetId,
                'error' => $e->getMessage(),
            ]);

            throw BudgetTrackingException::budgetNotFound(
                $request->tenantId,
                $request->budgetId
            );
        }
    }

    /**
     * Calculate budget variances.
     *
     * @param BudgetVarianceRequest $request The variance request parameters
     * @return BudgetVarianceResult The variance calculation result
     * @throws BudgetTrackingException If calculation fails
     */
    public function calculateVariances(BudgetVarianceRequest $request): BudgetVarianceResult
    {
        $this->logger->info('Calculating budget variances', [
            'tenant_id' => $request->tenantId,
            'period_id' => $request->periodId,
            'budget_id' => $request->budgetId,
        ]);

        try {
            $varianceData = $this->dataProvider->getVarianceAnalysis(
                $request->tenantId,
                $request->periodId,
                $request->budgetId
            );

            $variances = $varianceData['variances'] ?? [];

            // Filter by cost center if specified
            if ($request->costCenterId !== null) {
                $variances = array_values(array_filter(
                    $variances,
                    fn($v) => ($v['cost_center_id'] ?? null) === $request->costCenterId
                ));
            }

            // Filter by project if specified (Tier 2+ feature)
            if ($request->projectId !== null) {
                $variances = array_values(array_filter(
                    $variances,
                    fn($v) => ($v['project_id'] ?? null) === $request->projectId
                ));
            }

            // Calculate totals
            $totalBudgeted = array_sum(array_map(
                fn($v) => (float)($v['budgeted'] ?? 0),
                $variances
            ));
            $totalActual = array_sum(array_map(
                fn($v) => (float)($v['actual'] ?? 0),
                $variances
            ));
            $totalVariance = $totalBudgeted - $totalActual;

            return new BudgetVarianceResult(
                success: true,
                variances: $variances,
                totalBudgeted: (string)round($totalBudgeted, 2),
                totalActual: (string)round($totalActual, 2),
                totalVariance: (string)round($totalVariance, 2),
            );
        } catch (\Throwable $e) {
            $this->logger->error('Variance calculation failed', [
                'tenant_id' => $request->tenantId,
                'period_id' => $request->periodId,
                'error' => $e->getMessage(),
            ]);

            throw BudgetTrackingException::varianceCalculationFailed(
                $request->tenantId,
                $request->periodId,
                $e->getMessage(),
                $e
            );
        }
    }

    /**
     * Check budget thresholds.
     *
     * @param BudgetThresholdRequest $request The threshold check request parameters
     * @return BudgetThresholdResult The threshold check result
     */
    public function checkThresholds(BudgetThresholdRequest $request): BudgetThresholdResult
    {
        $this->logger->info('Checking budget thresholds', [
            'tenant_id' => $request->tenantId,
            'period_id' => $request->periodId,
            'thresholds' => implode(', ', array_map('strval', $request->thresholds)),
        ]);

        try {
            $varianceData = $this->dataProvider->getVarianceAnalysis(
                $request->tenantId,
                $request->periodId
            );

            $exceededThresholds = [];
            $warnings = [];

            $variances = $varianceData['variances'] ?? [];

            // Filter by cost center if specified
            if ($request->costCenterId !== null) {
                $variances = array_values(array_filter(
                    $variances,
                    fn($v) => ($v['cost_center_id'] ?? null) === $request->costCenterId
                ));
            }

            foreach ($variances as $variance) {
                $budgeted = (float)($variance['budgeted'] ?? 0);
                $actual = (float)($variance['actual'] ?? 0);

                if ($budgeted <= 0) {
                    continue;
                }

                $utilizationPercent = ($actual / $budgeted) * 100;

                // Sort thresholds descending to find highest exceeded
                $sortedThresholds = $request->thresholds;
                rsort($sortedThresholds);

                foreach ($sortedThresholds as $threshold) {
                    if ($utilizationPercent >= $threshold) {
                        $exceededThresholds[] = [
                            'budgetId' => $variance['budget_id'] ?? $variance['id'] ?? '',
                            'budgetName' => $variance['budget_name'] ?? $variance['name'] ?? 'Unknown',
                            'costCenterId' => $variance['cost_center_id'] ?? null,
                            'threshold' => $threshold,
                            'utilizationPercent' => round($utilizationPercent, 2),
                            'budgeted' => (string)$budgeted,
                            'actual' => (string)$actual,
                            'exceededAt' => date('Y-m-d H:i:s'),
                        ];

                        $warnings[] = sprintf(
                            'Budget %s at %.1f%% utilization (threshold: %d%%)',
                            $variance['budget_id'] ?? $variance['name'] ?? 'unknown',
                            $utilizationPercent,
                            $threshold
                        );
                        break; // Only report highest threshold exceeded
                    }
                }
            }

            return new BudgetThresholdResult(
                success: true,
                exceededThresholds: $exceededThresholds,
                warnings: $warnings,
            );
        } catch (\Throwable $e) {
            $this->logger->error('Threshold check failed', [
                'tenant_id' => $request->tenantId,
                'error' => $e->getMessage(),
            ]);

            return new BudgetThresholdResult(
                success: false,
                exceededThresholds: [],
                warnings: [],
                error: $e->getMessage(),
            );
        }
    }

    /**
     * Get budget data for a period.
     *
     * @param string $tenantId The tenant identifier
     * @param string $periodId The period identifier
     * @param string|null $budgetId Optional budget version ID
     * @return array<string, mixed> Budget data
     */
    public function getBudgetData(string $tenantId, string $periodId, ?string $budgetId = null): array
    {
        $this->logger->info('Getting budget data', [
            'tenant_id' => $tenantId,
            'period_id' => $periodId,
            'budget_id' => $budgetId,
        ]);

        return $this->dataProvider->getBudgetData($tenantId, $periodId, $budgetId);
    }

    /**
     * Get actual data for a period.
     *
     * @param string $tenantId The tenant identifier
     * @param string $periodId The period identifier
     * @return array<string, mixed> Actual data
     */
    public function getActualData(string $tenantId, string $periodId): array
    {
        $this->logger->info('Getting actual data', [
            'tenant_id' => $tenantId,
            'period_id' => $periodId,
        ]);

        return $this->dataProvider->getActualData($tenantId, $periodId);
    }
}
