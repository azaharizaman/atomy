<?php
declare(strict_types=1);

namespace Nexus\FinanceOperations\Coordinators;

use Nexus\FinanceOperations\Contracts\CostAllocationCoordinatorInterface;
use Nexus\FinanceOperations\Contracts\CostAccountingDataProviderInterface;
use Nexus\FinanceOperations\DTOs\CostAllocationRequest;
use Nexus\FinanceOperations\DTOs\CostAllocationResult;
use Nexus\FinanceOperations\DTOs\ProductCostRequest;
use Nexus\FinanceOperations\DTOs\ProductCostResult;
use Nexus\FinanceOperations\DTOs\PeriodicAllocationRequest;
use Nexus\FinanceOperations\DTOs\PeriodicAllocationResult;
use Nexus\FinanceOperations\Services\CostAllocationService;
use Nexus\FinanceOperations\Rules\CostCenterActiveRule;
use Nexus\FinanceOperations\Exceptions\CostAllocationException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for cost allocation operations.
 * 
 * This coordinator manages the flow of cost-related operations:
 * - Cost allocation execution
 * - Product costing
 * - Periodic allocation runs
 * 
 * Following the Advanced Orchestrator Pattern:
 * - Coordinators direct flow, they do not execute business logic
 * - Delegates to services for calculations and heavy lifting
 * - Uses rules for validation
 * - Uses data providers for data aggregation
 * 
 * @see ARCHITECTURE.md Section 4: The Advanced Orchestrator Pattern
 * @since 1.0.0
 */
final readonly class CostAllocationCoordinator implements CostAllocationCoordinatorInterface
{
    public function __construct(
        private CostAllocationService $costAllocationService,
        private CostAccountingDataProviderInterface $costDataProvider,
        private CostCenterActiveRule $costCenterActiveRule,
        private ?EventDispatcherInterface $eventDispatcher = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'CostAllocationCoordinator';
    }

    /**
     * @inheritDoc
     */
    public function hasRequiredData(string $tenantId, string $periodId): bool
    {
        try {
            $costs = $this->costDataProvider->getAllocatedCosts($tenantId, $periodId);
            return true; // If no exception, data is available
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function getSupportedOperations(): array
    {
        return [
            'allocate_costs',
            'calculate_product_cost',
            'run_periodic_allocation',
        ];
    }

    /**
     * @inheritDoc
     */
    public function allocateCosts(CostAllocationRequest $request): CostAllocationResult
    {
        $this->logger->info('Coordinating cost allocation request', [
            'tenant_id' => $request->tenantId,
            'source_pool_id' => $request->sourceCostPoolId,
            'target_count' => count($request->targetCostCenterIds),
        ]);

        try {
            // Validate cost centers are active
            $ruleResult = $this->costCenterActiveRule->check((object)[
                'tenantId' => $request->tenantId,
                'costCenterIds' => $request->targetCostCenterIds,
            ]);

            if (!$ruleResult->passed) {
                throw CostAllocationException::inactiveCostCenter(
                    $request->tenantId,
                    $request->targetCostCenterIds[0] ?? 'unknown'
                );
            }

            // Convert to service DTO
            $serviceRequest = new \Nexus\FinanceOperations\DTOs\CostAllocation\CostAllocationRequest(
                tenantId: $request->tenantId,
                periodId: $request->periodId,
                sourceCostPoolId: $request->sourceCostPoolId,
                targetCostCenterIds: $request->targetCostCenterIds,
                allocationMethod: $request->allocationMethod,
                options: $request->options,
            );

            // Delegate to service
            $serviceResult = $this->costAllocationService->allocate($serviceRequest);

            // Convert back to interface DTO
            $result = new CostAllocationResult(
                success: $serviceResult->success,
                periodId: $request->periodId,
                totalAllocated: (float)$serviceResult->totalAllocated,
                allocations: $serviceResult->allocations,
                errorMessage: $serviceResult->error,
            );

            // Dispatch event
            $this->eventDispatcher?->dispatch(new class($request->tenantId, $result) {
                public function __construct(
                    public string $tenantId,
                    public CostAllocationResult $result,
                ) {}
            });

            return $result;
        } catch (CostAllocationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Cost allocation coordination failed', [
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
     * @inheritDoc
     */
    public function calculateProductCost(ProductCostRequest $request): ProductCostResult
    {
        $this->logger->info('Coordinating product cost calculation', [
            'tenant_id' => $request->tenantId,
            'product_id' => $request->productId,
        ]);

        try {
            // Convert to service DTO
            $serviceRequest = new \Nexus\FinanceOperations\DTOs\CostAllocation\ProductCostRequest(
                tenantId: $request->tenantId,
                productId: $request->productId,
                periodId: $request->options['period_id'] ?? null,
                includeBOM: $request->options['include_bom'] ?? true,
                includeOverhead: $request->options['include_overhead'] ?? true,
            );

            // Delegate to service
            $serviceResult = $this->costAllocationService->calculateProductCost($serviceRequest);

            // Convert back to interface DTO
            $result = new ProductCostResult(
                success: $serviceResult->success,
                productId: $serviceResult->productId,
                totalCost: (float)$serviceResult->totalCost,
                costBreakdown: array_merge(
                    $serviceResult->costBreakdown,
                    [
                        'material_cost' => $serviceResult->materialCost,
                        'labor_cost' => $serviceResult->laborCost,
                        'overhead_cost' => $serviceResult->overheadCost,
                    ]
                ),
                errorMessage: $serviceResult->error,
            );

            // Dispatch event
            $this->eventDispatcher?->dispatch(new class($request->tenantId, $result) {
                public function __construct(
                    public string $tenantId,
                    public ProductCostResult $result,
                ) {}
            });

            return $result;
        } catch (CostAllocationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Product cost coordination failed', [
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
     * @inheritDoc
     */
    public function runPeriodicAllocation(PeriodicAllocationRequest $request): PeriodicAllocationResult
    {
        $this->logger->info('Coordinating periodic allocation run', [
            'tenant_id' => $request->tenantId,
            'period_id' => $request->periodId,
        ]);

        try {
            $processedCount = 0;
            $totalAllocated = 0.0;
            $details = [];

            // Get allocation rules from options or data provider
            $allocationRuleIds = $request->options['allocation_rule_ids'] ?? [];

            // Process each allocation rule
            foreach ($allocationRuleIds as $ruleId) {
                // Get rule details from data provider
                $ruleData = $this->costDataProvider->getCostPoolSummary($request->tenantId, $ruleId);

                // Create allocation request for this rule
                $allocationRequest = new CostAllocationRequest(
                    tenantId: $request->tenantId,
                    periodId: $request->periodId,
                    sourceCostPoolId: $ruleId,
                    targetCostCenterIds: $ruleData['target_centers'] ?? [],
                    allocationMethod: $ruleData['method'] ?? 'proportional',
                    options: $request->options,
                );

                $validateOnly = $request->options['validate_only'] ?? false;

                if (!$validateOnly) {
                    $result = $this->allocateCosts($allocationRequest);
                    $processedCount++;
                    $totalAllocated += (float)$result->totalAllocated;
                    $details[] = [
                        'rule_id' => $ruleId,
                        'allocated' => $result->totalAllocated,
                        'status' => 'success',
                    ];
                } else {
                    $details[] = [
                        'rule_id' => $ruleId,
                        'allocated' => '0',
                        'status' => 'validated',
                    ];
                }
            }

            return new PeriodicAllocationResult(
                success: true,
                periodId: $request->periodId,
                allocations: $details,
            );
        } catch (CostAllocationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Periodic allocation coordination failed', [
                'tenant_id' => $request->tenantId,
                'error' => $e->getMessage(),
            ]);

            return new PeriodicAllocationResult(
                success: false,
                periodId: $request->periodId,
                allocations: [],
                errorMessage: $e->getMessage(),
            );
        }
    }
}
