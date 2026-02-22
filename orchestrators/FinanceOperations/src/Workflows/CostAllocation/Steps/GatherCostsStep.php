<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Workflows\CostAllocation\Steps;

use Nexus\FinanceOperations\Contracts\WorkflowStepInterface;
use Nexus\FinanceOperations\DTOs\WorkflowStepContext;
use Nexus\FinanceOperations\DTOs\WorkflowStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Workflow step: Gather Costs.
 *
 * Forward action: Collects cost data from various sources including
 * departments, projects, activities, and overhead pools.
 * 
 * Compensation: Clears gathered cost data and temporary records.
 *
 * @see WorkflowStepInterface
 * @since 1.0.0
 */
final readonly class GatherCostsStep implements WorkflowStepInterface
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
        return 'gather_costs';
    }

    /**
     * Execute the forward action: Gather costs from various sources.
     *
     * @param WorkflowStepContext $context The step context
     * @return WorkflowStepResult The step result
     */
    public function execute(WorkflowStepContext $context): WorkflowStepResult
    {
        $this->getLogger()->info('Starting cost gathering', [
            'workflow_id' => $context->workflowId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            $fiscalPeriodId = $context->get('fiscal_period_id');
            $allocationType = $context->get('allocation_type');
            $costCenterIds = $context->get('cost_center_ids');

            if ($fiscalPeriodId === null) {
                return WorkflowStepResult::failure(
                    stepName: $this->getName(),
                    error: 'Fiscal period ID is required for cost gathering',
                );
            }

            if ($allocationType === null) {
                return WorkflowStepResult::failure(
                    stepName: $this->getName(),
                    error: 'Allocation type is required for cost gathering',
                );
            }

            // Simulate cost gathering
            // In production, this would call the Finance/Budget packages via adapter
            $gatheringId = sprintf('CG-%s-%s', $fiscalPeriodId, bin2hex(random_bytes(8)));
            
            // Simulated cost data from various sources
            $costSources = [
                'direct_labor' => [
                    'source_type' => 'payroll',
                    'total_amount' => 250000.00,
                    'currency' => 'MYR',
                    'cost_centers' => [
                        'CC001' => 100000.00,
                        'CC002' => 75000.00,
                        'CC003' => 75000.00,
                    ],
                ],
                'direct_materials' => [
                    'source_type' => 'inventory',
                    'total_amount' => 180000.00,
                    'currency' => 'MYR',
                    'cost_centers' => [
                        'CC001' => 80000.00,
                        'CC002' => 60000.00,
                        'CC003' => 40000.00,
                    ],
                ],
                'overhead' => [
                    'source_type' => 'gl_accounts',
                    'total_amount' => 120000.00,
                    'currency' => 'MYR',
                    'cost_centers' => [
                        'CC001' => 50000.00,
                        'CC002' => 40000.00,
                        'CC003' => 30000.00,
                    ],
                ],
                'utilities' => [
                    'source_type' => 'payable',
                    'total_amount' => 45000.00,
                    'currency' => 'MYR',
                    'cost_centers' => [
                        'CC001' => 20000.00,
                        'CC002' => 15000.00,
                        'CC003' => 10000.00,
                    ],
                ],
            ];

            // Filter by cost center if specified
            if ($costCenterIds !== null) {
                $filteredSources = [];
                foreach ($costSources as $sourceName => $sourceData) {
                    $filteredCenters = array_intersect_key(
                        $sourceData['cost_centers'],
                        array_flip($costCenterIds)
                    );
                    if (!empty($filteredCenters)) {
                        $filteredSources[$sourceName] = [
                            ...$sourceData,
                            'cost_centers' => $filteredCenters,
                            'total_amount' => array_sum($filteredCenters),
                        ];
                    }
                }
                $costSources = $filteredSources;
            }

            $totalCosts = array_sum(array_column($costSources, 'total_amount'));

            $gatheringResult = [
                'gathering_id' => $gatheringId,
                'fiscal_period_id' => $fiscalPeriodId,
                'allocation_type' => $allocationType,
                'gathered_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'currency' => 'MYR',
                'total_costs' => $totalCosts,
                'source_count' => count($costSources),
                'cost_sources' => $costSources,
                'cost_center_count' => count($costCenterIds ?? array_unique(array_merge(
                    ...array_map(fn($s) => array_keys($s['cost_centers']), $costSources)
                ))),
                'summary' => [
                    'direct_costs' => ($costSources['direct_labor']['total_amount'] ?? 0) + 
                                     ($costSources['direct_materials']['total_amount'] ?? 0),
                    'indirect_costs' => ($costSources['overhead']['total_amount'] ?? 0) + 
                                       ($costSources['utilities']['total_amount'] ?? 0),
                ],
            ];

            $this->getLogger()->info('Cost gathering completed', [
                'gathering_id' => $gatheringId,
                'total_costs' => $totalCosts,
                'source_count' => count($costSources),
            ]);

            return WorkflowStepResult::success(
                stepName: $this->getName(),
                data: $gatheringResult,
            );
        } catch (\Throwable $e) {
            $this->getLogger()->error('Cost gathering failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return WorkflowStepResult::failure(
                stepName: $this->getName(),
                error: 'Cost gathering failed: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Execute the compensation action: Clear gathered cost data.
     *
     * @param WorkflowStepContext $context The step context
     * @return WorkflowStepResult The compensation result
     */
    public function compensate(WorkflowStepContext $context): WorkflowStepResult
    {
        $this->getLogger()->info('Compensating: Clearing gathered cost data', [
            'workflow_id' => $context->workflowId,
        ]);

        try {
            $previousResult = $context->getPreviousStepResult($this->getName());
            $gatheringId = $previousResult['gathering_id'] ?? null;

            if ($gatheringId === null) {
                return WorkflowStepResult::success(
                    stepName: $this->getName() . '_compensation',
                    data: ['message' => 'No gathered cost data to clear'],
                );
            }

            // In production, this would clear temporary cost data
            $this->getLogger()->info('Gathered cost data cleared', [
                'gathering_id' => $gatheringId,
            ]);

            return WorkflowStepResult::success(
                stepName: $this->getName() . '_compensation',
                data: [
                    'cleared_gathering_id' => $gatheringId,
                    'reason' => 'Cost allocation workflow compensation',
                    'cleared_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                ],
            );
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to clear gathered cost data during compensation', [
                'error' => $e->getMessage(),
            ]);

            return WorkflowStepResult::failure(
                stepName: $this->getName() . '_compensation',
                error: 'Failed to clear gathered cost data: ' . $e->getMessage(),
            );
        }
    }
}
