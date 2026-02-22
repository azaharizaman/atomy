<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Workflows\CostAllocation;

use Nexus\FinanceOperations\Contracts\WorkflowStepInterface;
use Nexus\FinanceOperations\Workflows\AbstractFinanceWorkflow;
use Nexus\FinanceOperations\Workflows\CostAllocation\Steps\GatherCostsStep;
use Nexus\FinanceOperations\Workflows\CostAllocation\Steps\ApplyAllocationRulesStep;
use Nexus\FinanceOperations\Workflows\CostAllocation\Steps\PostAllocatedCostsStep;
use Psr\Log\LoggerInterface;

/**
 * CostAllocationWorkflow - Saga for cost allocation processing.
 *
 * This workflow orchestrates the complete cost allocation process including:
 * - Gathering costs from various sources (departments, projects, activities)
 * - Applying allocation rules based on cost drivers and bases
 * - Posting allocated costs to the General Ledger
 *
 * States:
 * - INITIATED: Cost allocation started
 * - GATHERING: Collecting cost data from sources
 * - ALLOCATING: Applying allocation rules
 * - POSTING: Posting allocated costs to GL
 * - COMPLETED: Cost allocation completed successfully
 * - FAILED: Workflow failed, compensation triggered
 * - COMPENSATED: Workflow rolled back
 *
 * @see AbstractFinanceWorkflow
 * @since 1.0.0
 */
final readonly class CostAllocationWorkflow extends AbstractFinanceWorkflow
{
    /**
     * Workflow state constants.
     */
    public const STATE_INITIATED = 'INITIATED';
    public const STATE_GATHERING = 'GATHERING';
    public const STATE_ALLOCATING = 'ALLOCATING';
    public const STATE_POSTING = 'POSTING';
    public const STATE_COMPLETED = 'COMPLETED';
    public const STATE_FAILED = 'FAILED';
    public const STATE_COMPENSATED = 'COMPENSATED';

    /**
     * @param WorkflowStepInterface[] $steps
     * @param object|null $storage
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        array $steps = [],
        ?object $storage = null,
        ?LoggerInterface $logger = null,
    ) {
        // Initialize workflow steps in execution order
        $workflowSteps = $steps ?: [
            new GatherCostsStep($logger),
            new ApplyAllocationRulesStep($logger),
            new PostAllocatedCostsStep($logger),
        ];

        parent::__construct(
            steps: $workflowSteps,
            storage: $storage,
            logger: $logger ?? new \Psr\Log\NullLogger(),
        );
    }

    /**
     * Get the workflow name for identification and logging.
     *
     * @return string
     */
    public function getWorkflowName(): string
    {
        return 'Cost Allocation Workflow';
    }

    /**
     * Get required context fields for this workflow.
     *
     * @return array<string>
     */
    public function getRequiredContextFields(): array
    {
        return [
            'tenant_id',
            'fiscal_period_id',
            'allocation_type',
        ];
    }

    /**
     * Get optional context fields with defaults.
     *
     * @return array<string, mixed>
     */
    public function getOptionalContextFields(): array
    {
        return [
            'cost_center_ids' => null, // null means all cost centers
            'allocation_method' => 'proportional',
            'cost_driver' => 'revenue',
            'rounding_method' => 'round',
            'post_automatically' => true,
            'dry_run' => false,
        ];
    }

    /**
     * Get a step by its name.
     *
     * @param string $stepName Step name to find
     * @return WorkflowStepInterface|null
     */
    public function getStep(string $stepName): ?WorkflowStepInterface
    {
        foreach ($this->steps as $step) {
            if ($step->getName() === $stepName) {
                return $step;
            }
        }

        return null;
    }
}
