<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Workflows\DepreciationRun;

use Nexus\FinanceOperations\Contracts\WorkflowStepInterface;
use Nexus\FinanceOperations\Workflows\AbstractFinanceWorkflow;
use Nexus\FinanceOperations\Workflows\DepreciationRun\Steps\CalculateDepreciationStep;
use Nexus\FinanceOperations\Workflows\DepreciationRun\Steps\ValidateDepreciationStep;
use Nexus\FinanceOperations\Workflows\DepreciationRun\Steps\PostToGLStep;
use Nexus\FinanceOperations\Workflows\DepreciationRun\Steps\UpdateAssetRegisterStep;
use Psr\Log\LoggerInterface;

/**
 * DepreciationRunWorkflow - Saga for fixed asset depreciation processing.
 *
 * This workflow orchestrates the complete depreciation run process including:
 * - Calculation of depreciation for all eligible assets
 * - Validation of calculated amounts against business rules
 * - Posting depreciation entries to the General Ledger
 * - Updating the asset register with new book values
 *
 * States:
 * - INITIATED: Depreciation run started
 * - CALCULATING: Computing depreciation amounts
 * - VALIDATING: Validating calculations
 * - POSTING: Posting to General Ledger
 * - UPDATING_REGISTER: Updating asset register
 * - COMPLETED: Depreciation run completed successfully
 * - FAILED: Workflow failed, compensation triggered
 * - COMPENSATED: Workflow rolled back
 *
 * @see AbstractFinanceWorkflow
 * @since 1.0.0
 */
final readonly class DepreciationRunWorkflow extends AbstractFinanceWorkflow
{
    /**
     * Workflow state constants.
     */
    public const STATE_INITIATED = 'INITIATED';
    public const STATE_CALCULATING = 'CALCULATING';
    public const STATE_VALIDATING = 'VALIDATING';
    public const STATE_POSTING = 'POSTING';
    public const STATE_UPDATING_REGISTER = 'UPDATING_REGISTER';
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
            new CalculateDepreciationStep($logger),
            new ValidateDepreciationStep($logger),
            new PostToGLStep($logger),
            new UpdateAssetRegisterStep($logger),
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
        return 'Depreciation Run Workflow';
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
            'depreciation_method',
            'run_date',
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
            'asset_ids' => null, // null means all eligible assets
            'skip_validation' => false,
            'post_automatically' => true,
            'batch_size' => 100,
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
