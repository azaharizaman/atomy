<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Workflows\CostAllocation\Steps;

use Nexus\FinanceOperations\Contracts\WorkflowStepInterface;
use Nexus\FinanceOperations\DTOs\WorkflowStepContext;
use Nexus\FinanceOperations\DTOs\WorkflowStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Workflow step: Apply Allocation Rules.
 *
 * Forward action: Applies allocation rules to distribute costs
 * based on cost drivers and allocation bases.
 * 
 * Compensation: Clears allocation results and temporary calculations.
 *
 * @see WorkflowStepInterface
 * @since 1.0.0
 */
final readonly class ApplyAllocationRulesStep implements WorkflowStepInterface
{
    /**
     * @param LoggerInterface|null $logger PSR-3 compliant logger
     */
    public function __construct(
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * Get the logger instance, or a NullLogger if none was injected.
     *
     * @return LoggerInterface
     */
    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    /**
     * Get the step name for identification and logging.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'apply_allocation_rules';
    }

    /**
     * Execute the forward action: Apply allocation rules to gathered costs.
     *
     * @param WorkflowStepContext $context The step context
     * @return WorkflowStepResult The step result
     */
    public function execute(WorkflowStepContext $context): WorkflowStepResult
    {
        $this->getLogger()->info('Starting allocation rules application', [
            'workflow_id' => $context->workflowId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            // Get the gathered costs from previous step
            $gatheredCosts = $context->getPreviousStepResult('gather_costs');
            
            if ($gatheredCosts === null) {
                return WorkflowStepResult::failure(
                    stepName: $this->getName(),
                    error: 'No gathered costs found to allocate',
                );
            }

            $gatheringId = $gatheredCosts['gathering_id'] ?? null;
            $allocationMethod = $context->get('allocation_method', 'proportional');
            $costDriver = $context->get('cost_driver', 'revenue');
            $roundingMethod = $context->get('rounding_method', 'round');

            // Simulate allocation rules
            // In production, this would call allocation rules from the Rules directory
            $allocationId = sprintf('AR-%s', $gatheringId);
            
            // Simulated cost drivers (basis for allocation)
            $costDrivers = [
                'CC001' => [
                    'revenue' => 500000.00,
                    'headcount' => 25,
                    'floor_area' => 1500,
                    'machine_hours' => 1200,
                ],
                'CC002' => [
                    'revenue' => 350000.00,
                    'headcount' => 18,
                    'floor_area' => 1000,
                    'machine_hours' => 800,
                ],
                'CC003' => [
                    'revenue' => 150000.00,
                    'headcount' => 12,
                    'floor_area' => 500,
                    'machine_hours' => 400,
                ],
            ];

            // Calculate driver totals
            $driverTotals = [];
            foreach ($costDrivers as $cc => $drivers) {
                foreach ($drivers as $driver => $value) {
                    $driverTotals[$driver] = ($driverTotals[$driver] ?? 0) + $value;
                }
            }

            // Apply allocation rules
            $allocations = [];
            $costSources = $gatheredCosts['cost_sources'] ?? [];
            
            foreach ($costSources as $sourceName => $sourceData) {
                $sourceAllocations = [];
                $totalToAllocate = $sourceData['total_amount'];
                
                // Determine allocation basis based on source type
                $allocationBasis = $this->determineAllocationBasis($sourceName, $costDriver);
                $basisTotal = $driverTotals[$allocationBasis] ?? 1;

                foreach ($costDrivers as $costCenter => $drivers) {
                    $basisValue = $drivers[$allocationBasis] ?? 0;
                    $allocationPercentage = $basisValue / $basisTotal;
                    
                    $allocatedAmount = match ($roundingMethod) {
                        'ceil' => ceil($totalToAllocate * $allocationPercentage * 100) / 100,
                        'floor' => floor($totalToAllocate * $allocationPercentage * 100) / 100,
                        default => round($totalToAllocate * $allocationPercentage, 2),
                    };

                    $sourceAllocations[$costCenter] = [
                        'basis_type' => $allocationBasis,
                        'basis_value' => $basisValue,
                        'basis_total' => $basisTotal,
                        'allocation_percentage' => $allocationPercentage * 100,
                        'allocated_amount' => $allocatedAmount,
                    ];
                }

                $allocations[$sourceName] = [
                    'source_type' => $sourceData['source_type'],
                    'total_allocated' => array_sum(array_column($sourceAllocations, 'allocated_amount')),
                    'allocation_basis' => $allocationBasis,
                    'allocations' => $sourceAllocations,
                ];
            }

            // Calculate totals per cost center
            $costCenterTotals = [];
            foreach ($allocations as $sourceAlloc) {
                foreach ($sourceAlloc['allocations'] as $costCenter => $allocData) {
                    if (!isset($costCenterTotals[$costCenter])) {
                        $costCenterTotals[$costCenter] = 0;
                    }
                    $costCenterTotals[$costCenter] += $allocData['allocated_amount'];
                }
            }

            $allocationResult = [
                'allocation_id' => $allocationId,
                'gathering_id' => $gatheringId,
                'allocation_method' => $allocationMethod,
                'cost_driver' => $costDriver,
                'rounding_method' => $roundingMethod,
                'allocated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'currency' => $gatheredCosts['currency'] ?? 'MYR',
                'total_allocated' => array_sum($costCenterTotals),
                'source_count' => count($allocations),
                'allocations' => $allocations,
                'cost_center_totals' => $costCenterTotals,
                'cost_drivers_used' => $costDrivers,
                'driver_totals' => $driverTotals,
            ];

            $this->getLogger()->info('Allocation rules applied', [
                'allocation_id' => $allocationId,
                'total_allocated' => $allocationResult['total_allocated'],
                'cost_centers' => count($costCenterTotals),
            ]);

            return WorkflowStepResult::success(
                stepName: $this->getName(),
                data: $allocationResult,
            );
        } catch (\Throwable $e) {
            $this->getLogger()->error('Allocation rules application failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return WorkflowStepResult::failure(
                stepName: $this->getName(),
                error: 'Allocation rules application failed: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Execute the compensation action: Clear allocation results.
     *
     * @param WorkflowStepContext $context The step context
     * @return WorkflowStepResult The compensation result
     */
    public function compensate(WorkflowStepContext $context): WorkflowStepResult
    {
        $this->getLogger()->info('Compensating: Clearing allocation results', [
            'workflow_id' => $context->workflowId,
        ]);

        try {
            $previousResult = $context->getPreviousStepResult($this->getName());
            $allocationId = $previousResult['allocation_id'] ?? null;

            if ($allocationId === null) {
                return WorkflowStepResult::success(
                    stepName: $this->getName() . '_compensation',
                    data: ['message' => 'No allocation results to clear'],
                );
            }

            // In production, this would clear allocation calculations
            $this->getLogger()->info('Allocation results cleared', [
                'allocation_id' => $allocationId,
            ]);

            return WorkflowStepResult::success(
                stepName: $this->getName() . '_compensation',
                data: [
                    'cleared_allocation_id' => $allocationId,
                    'reason' => 'Cost allocation workflow compensation',
                    'cleared_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                ],
            );
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to clear allocation results during compensation', [
                'error' => $e->getMessage(),
            ]);

            return WorkflowStepResult::failure(
                stepName: $this->getName() . '_compensation',
                error: 'Failed to clear allocation results: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Determine the allocation basis for a cost source.
     *
     * @param string $sourceName Cost source name
     * @param string $defaultDriver Default cost driver
     * @return string Allocation basis
     */
    private function determineAllocationBasis(string $sourceName, string $defaultDriver): string
    {
        return match ($sourceName) {
            'direct_labor' => 'headcount',
            'direct_materials' => 'revenue',
            'utilities' => 'floor_area',
            'overhead' => $defaultDriver,
            default => $defaultDriver,
        };
    }
}
