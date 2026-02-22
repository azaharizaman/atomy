<?php
declare(strict_types=1);

namespace Nexus\FinanceOperations\Services;

use Nexus\FinanceOperations\Contracts\CostAccountingDataProviderInterface;
use Nexus\FinanceOperations\DTOs\CostAllocation\CostAllocationRequest;
use Nexus\FinanceOperations\DTOs\CostAllocation\CostAllocationResult;
use Nexus\FinanceOperations\DTOs\CostAllocation\ProductCostRequest;
use Nexus\FinanceOperations\DTOs\CostAllocation\ProductCostResult;
use Nexus\FinanceOperations\Exceptions\CostAllocationException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for cost allocation and product costing operations.
 * 
 * This service handles:
 * - Cost allocation calculations
 * - Product cost rollups
 * - Allocation method application
 * 
 * Following Advanced Orchestrator Pattern v1.1:
 * Services handle the "heavy lifting" - calculations and cross-boundary logic.
 * 
 * @since 1.0.0
 */
final readonly class CostAllocationService
{
    public function __construct(
        private CostAccountingDataProviderInterface $dataProvider,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Allocate costs from a pool to cost centers.
     *
     * @param CostAllocationRequest $request The allocation request parameters
     * @return CostAllocationResult The allocation result
     * @throws CostAllocationException If allocation fails
     */
    public function allocate(CostAllocationRequest $request): CostAllocationResult
    {
        $this->logger->info('Processing cost allocation', [
            'tenant_id' => $request->tenantId,
            'source_pool_id' => $request->sourceCostPoolId,
            'method' => $request->allocationMethod,
        ]);

        try {
            // Get source pool data
            $poolData = $this->dataProvider->getCostPoolSummary(
                $request->tenantId,
                $request->sourceCostPoolId
            );

            $totalAmount = (float)($poolData['total_amount'] ?? 0);
            $targetCount = count($request->targetCostCenterIds);

            if ($targetCount === 0) {
                throw CostAllocationException::allocationFailed(
                    $request->tenantId,
                    $request->sourceCostPoolId,
                    'No target cost centers specified'
                );
            }

            // Calculate allocations based on method
            $allocations = $this->calculateAllocations(
                $totalAmount,
                $request->targetCostCenterIds,
                $request->allocationMethod,
                $request->options
            );

            $allocationId = $this->generateAllocationId();

            return new CostAllocationResult(
                success: true,
                allocationId: $allocationId,
                totalAllocated: (string)$totalAmount,
                allocations: $allocations,
                journalEntries: $this->prepareJournalEntries($allocations, $request),
            );
        } catch (CostAllocationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Cost allocation failed', [
                'tenant_id' => $request->tenantId,
                'error' => $e->getMessage(),
            ]);

            throw CostAllocationException::allocationFailed(
                $request->tenantId,
                $request->sourceCostPoolId,
                $e->getMessage(),
                $e
            );
        }
    }

    /**
     * Calculate product cost.
     *
     * @param ProductCostRequest $request The product cost request parameters
     * @return ProductCostResult The calculated product cost
     * @throws CostAllocationException If costing fails
     */
    public function calculateProductCost(ProductCostRequest $request): ProductCostResult
    {
        $this->logger->info('Calculating product cost', [
            'tenant_id' => $request->tenantId,
            'product_id' => $request->productId,
        ]);

        try {
            $costData = $this->dataProvider->getProductCostData(
                $request->tenantId,
                $request->productId
            );

            $materialCost = $costData['material_cost'] ?? '0';
            $laborCost = $costData['labor_cost'] ?? '0';
            $overheadCost = $costData['overhead_cost'] ?? '0';

            // Calculate total
            $totalCost = (string)(
                (float)$materialCost + 
                (float)$laborCost + 
                (float)$overheadCost
            );

            return new ProductCostResult(
                success: true,
                productId: $request->productId,
                materialCost: $materialCost,
                laborCost: $laborCost,
                overheadCost: $overheadCost,
                totalCost: $totalCost,
                costBreakdown: $costData['breakdown'] ?? [],
            );
        } catch (\Throwable $e) {
            $this->logger->error('Product costing failed', [
                'tenant_id' => $request->tenantId,
                'product_id' => $request->productId,
                'error' => $e->getMessage(),
            ]);

            throw CostAllocationException::productCostingFailed(
                $request->tenantId,
                $request->productId,
                $e->getMessage(),
                $e
            );
        }
    }

    /**
     * Get cost center summary with allocated costs.
     *
     * @param string $tenantId The tenant identifier
     * @param string $costCenterId The cost center identifier
     * @return array<string, mixed> Cost center summary data
     */
    public function getCostCenterSummary(string $tenantId, string $costCenterId): array
    {
        $this->logger->info('Getting cost center summary', [
            'tenant_id' => $tenantId,
            'cost_center_id' => $costCenterId,
        ]);

        return $this->dataProvider->getCostCenterSummary($tenantId, $costCenterId);
    }

    /**
     * Get allocated costs for a period.
     *
     * @param string $tenantId The tenant identifier
     * @param string $periodId The period identifier
     * @return array<string, mixed> Allocated costs data
     */
    public function getAllocatedCosts(string $tenantId, string $periodId): array
    {
        $this->logger->info('Getting allocated costs', [
            'tenant_id' => $tenantId,
            'period_id' => $periodId,
        ]);

        return $this->dataProvider->getAllocatedCosts($tenantId, $periodId);
    }

    /**
     * Calculate allocations based on method.
     *
     * @param float $totalAmount Total amount to allocate
     * @param array<string> $targetCostCenterIds Target cost center IDs
     * @param string $method Allocation method ('equal', 'proportional', 'manual')
     * @param array<string, mixed> $options Additional allocation options
     * @return array<int, array{costCenterId: string, amount: string, percentage: float}> Allocation breakdown
     * @throws \InvalidArgumentException If unknown allocation method
     */
    private function calculateAllocations(
        float $totalAmount,
        array $targetCostCenterIds,
        string $method,
        array $options
    ): array {
        $allocations = [];
        $count = count($targetCostCenterIds);

        switch ($method) {
            case 'equal':
                $perCenter = $totalAmount / $count;
                foreach ($targetCostCenterIds as $costCenterId) {
                    $allocations[] = [
                        'costCenterId' => $costCenterId,
                        'amount' => (string)round($perCenter, 2),
                        'percentage' => round((100 / $count), 2),
                    ];
                }
                break;

            case 'proportional':
                // Use provided weights or equal distribution
                $weights = $options['weights'] ?? array_fill(0, $count, 1);
                $totalWeight = (float)array_sum($weights);
                
                foreach ($targetCostCenterIds as $index => $costCenterId) {
                    $weight = (float)($weights[$index] ?? 1);
                    $percentage = ($weight / $totalWeight) * 100;
                    $amount = ($weight / $totalWeight) * $totalAmount;
                    
                    $allocations[] = [
                        'costCenterId' => $costCenterId,
                        'amount' => (string)round($amount, 2),
                        'percentage' => round($percentage, 2),
                    ];
                }
                break;

            case 'manual':
                // Manual allocations must be provided in options
                $manualAllocations = $options['allocations'] ?? [];
                if (empty($manualAllocations)) {
                    throw new \InvalidArgumentException('Manual allocation requires allocations in options');
                }
                
                foreach ($targetCostCenterIds as $index => $costCenterId) {
                    $amount = (float)($manualAllocations[$index] ?? $manualAllocations[$costCenterId] ?? 0);
                    $percentage = $totalAmount > 0 ? ($amount / $totalAmount) * 100 : 0;
                    
                    $allocations[] = [
                        'costCenterId' => $costCenterId,
                        'amount' => (string)round($amount, 2),
                        'percentage' => round($percentage, 2),
                    ];
                }
                break;

            default:
                throw new \InvalidArgumentException("Unknown allocation method: {$method}");
        }

        return $allocations;
    }

    /**
     * Prepare journal entries for allocations.
     *
     * @param array<int, array{costCenterId: string, amount: string, percentage: float}> $allocations Allocation breakdown
     * @param CostAllocationRequest $request The original request
     * @return array<int, array<string, mixed>> Journal entry data
     */
    private function prepareJournalEntries(array $allocations, CostAllocationRequest $request): array
    {
        $entries = [];
        
        foreach ($allocations as $allocation) {
            $entries[] = [
                'type' => 'allocation',
                'cost_center_id' => $allocation['costCenterId'],
                'amount' => $allocation['amount'],
                'source_pool_id' => $request->sourceCostPoolId,
                'period_id' => $request->periodId,
            ];
        }

        return $entries;
    }

    /**
     * Generate a unique allocation ID.
     *
     * @return string Unique allocation identifier
     */
    private function generateAllocationId(): string
    {
        return 'ALLOC-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
    }
}
