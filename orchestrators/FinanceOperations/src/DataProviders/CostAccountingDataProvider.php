<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DataProviders;

use Nexus\FinanceOperations\Contracts\CostAccountingDataProviderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Data provider for cost accounting data aggregation.
 *
 * Aggregates data from:
 * - CostAccounting package (cost pools, cost centers, allocations)
 * - JournalEntry package (actual costs from GL)
 * - Budget package (budgeted costs)
 *
 * Following Advanced Orchestrator Pattern v1.1:
 * DataProviders abstract data fetching from Coordinators.
 *
 * @since 1.0.0
 */
final readonly class CostAccountingDataProvider implements CostAccountingDataProviderInterface
{
    public function __construct(
        private object $costManager,  // CostAccountingManagerInterface
        private object $glQuery,  // LedgerQueryInterface
        private ?object $budgetQuery = null,  // BudgetQueryInterface
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * @inheritDoc
     */
    public function getCostPoolSummary(string $tenantId, string $poolId): array
    {
        $this->logger->debug('Fetching cost pool summary', [
            'tenant_id' => $tenantId,
            'pool_id' => $poolId,
        ]);

        try {
            $pool = $this->costManager->getCostPool($tenantId, $poolId);
            $allocations = $this->costManager->getPoolAllocations($tenantId, $poolId);

            return [
                'pool_id' => $poolId,
                'pool_name' => $pool->getName(),
                'pool_type' => $pool->getType(),
                'total_amount' => $pool->getTotalAmount(),
                'currency' => $pool->getCurrency(),
                'allocation_count' => count($allocations),
                'allocations' => array_map(fn($a) => [
                    'cost_center_id' => $a->getCostCenterId(),
                    'cost_center_name' => $a->getCostCenterName(),
                    'amount' => $a->getAmount(),
                    'percentage' => $a->getPercentage(),
                    'allocation_method' => $a->getMethod(),
                ], $allocations),
                'status' => $pool->getStatus(),
                'created_at' => $pool->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch cost pool summary', [
                'tenant_id' => $tenantId,
                'pool_id' => $poolId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function getAllocatedCosts(string $tenantId, string $periodId): array
    {
        $this->logger->debug('Fetching allocated costs', [
            'tenant_id' => $tenantId,
            'period_id' => $periodId,
        ]);

        try {
            $allocations = $this->costManager->getPeriodAllocations($tenantId, $periodId);

            $byCostCenter = [];
            $totalAllocated = '0';

            foreach ($allocations as $allocation) {
                $costCenterId = $allocation->getCostCenterId();
                if (!isset($byCostCenter[$costCenterId])) {
                    $byCostCenter[$costCenterId] = [
                        'cost_center_id' => $costCenterId,
                        'cost_center_name' => $allocation->getCostCenterName(),
                        'total' => '0',
                        'details' => [],
                    ];
                }
                $byCostCenter[$costCenterId]['details'][] = [
                    'source_pool' => $allocation->getSourcePool(),
                    'source_pool_name' => $allocation->getSourcePoolName(),
                    'amount' => $allocation->getAmount(),
                    'method' => $allocation->getMethod(),
                    'allocated_at' => $allocation->getAllocatedAt()->format('Y-m-d H:i:s'),
                ];
                // Accumulate total
                $byCostCenter[$costCenterId]['total'] = bcadd(
                    $byCostCenter[$costCenterId]['total'],
                    $allocation->getAmount(),
                    2
                );
            }

            // Calculate total allocated
            foreach ($byCostCenter as $center) {
                $totalAllocated = bcadd($totalAllocated, $center['total'], 2);
            }

            return [
                'period_id' => $periodId,
                'total_allocated' => $totalAllocated,
                'by_cost_center' => array_values($byCostCenter),
                'allocation_count' => count($allocations),
                'cost_center_count' => count($byCostCenter),
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch allocated costs', [
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
    public function getProductCostData(string $tenantId, string $productId): array
    {
        $this->logger->debug('Fetching product cost data', [
            'tenant_id' => $tenantId,
            'product_id' => $productId,
        ]);

        try {
            $productCost = $this->costManager->getProductCost($tenantId, $productId);

            return [
                'product_id' => $productId,
                'material_cost' => $productCost->getMaterialCost(),
                'labor_cost' => $productCost->getLaborCost(),
                'overhead_cost' => $productCost->getOverheadCost(),
                'total_cost' => $productCost->getTotalCost(),
                'currency' => $productCost->getCurrency(),
                'cost_method' => $productCost->getCostMethod(),
                'last_updated' => $productCost->getLastUpdated()->format('Y-m-d H:i:s'),
                'cost_breakdown' => $productCost->getBreakdown(),
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch product cost data', [
                'tenant_id' => $tenantId,
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function getCostCenterSummary(string $tenantId, string $costCenterId): array
    {
        $this->logger->debug('Fetching cost center summary', [
            'tenant_id' => $tenantId,
            'cost_center_id' => $costCenterId,
        ]);

        try {
            $costCenter = $this->costManager->getCostCenter($tenantId, $costCenterId);
            $actualCosts = $this->glQuery->getCostCenterBalance($tenantId, $costCenterId);

            $budgetData = null;
            if ($this->budgetQuery !== null) {
                try {
                    $budget = $this->budgetQuery->getCostCenterBudget($tenantId, $costCenterId);
                    $budgetData = [
                        'amount' => $budget->getAmount(),
                        'currency' => $budget->getCurrency(),
                        'period_id' => $budget->getPeriodId(),
                    ];
                } catch (\Throwable $e) {
                    $this->logger->warning('Failed to fetch cost center budget', [
                        'tenant_id' => $tenantId,
                        'cost_center_id' => $costCenterId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $actualBalance = $actualCosts->getBalance();
            $variance = $budgetData !== null
                ? bcsub($budgetData['amount'], $actualBalance, 2)
                : null;

            return [
                'cost_center_id' => $costCenterId,
                'name' => $costCenter->getName(),
                'code' => $costCenter->getCode(),
                'is_active' => $costCenter->isActive(),
                'actual_costs' => $actualBalance,
                'currency' => $actualCosts->getCurrency(),
                'budget' => $budgetData,
                'variance' => $variance,
                'variance_percent' => $budgetData !== null && bccomp($budgetData['amount'], '0', 2) !== 0
                    ? (float) bcmul(bcdiv($variance, $budgetData['amount'], 4), '100', 2)
                    : null,
                'responsible_person' => $costCenter->getResponsiblePerson(),
                'department' => $costCenter->getDepartment(),
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch cost center summary', [
                'tenant_id' => $tenantId,
                'cost_center_id' => $costCenterId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
