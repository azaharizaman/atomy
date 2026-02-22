<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Workflows\CashReconciliation;

use Nexus\FinanceOperations\Contracts\WorkflowStepInterface;
use Nexus\FinanceOperations\Workflows\AbstractFinanceWorkflow;
use Nexus\FinanceOperations\Workflows\CashReconciliation\Steps\MatchTransactionsStep;
use Nexus\FinanceOperations\Workflows\CashReconciliation\Steps\IdentifyDiscrepanciesStep;
use Nexus\FinanceOperations\Workflows\CashReconciliation\Steps\CreateAdjustingEntriesStep;
use Psr\Log\LoggerInterface;

/**
 * CashReconciliationWorkflow - Saga for cash/bank reconciliation processing.
 *
 * This workflow orchestrates the complete cash reconciliation process including:
 * - Matching bank transactions with book records
 * - Identifying discrepancies between bank and book balances
 * - Creating adjusting entries for identified differences
 *
 * States:
 * - INITIATED: Reconciliation started
 * - MATCHING: Matching transactions in progress
 * - IDENTIFYING_DISCREPANCIES: Analyzing differences
 * - CREATING_ADJUSTMENTS: Creating adjusting entries
 * - COMPLETED: Reconciliation completed successfully
 * - FAILED: Workflow failed, compensation triggered
 * - COMPENSATED: Workflow rolled back
 *
 * @see AbstractFinanceWorkflow
 * @since 1.0.0
 */
final readonly class CashReconciliationWorkflow extends AbstractFinanceWorkflow
{
    /**
     * Workflow state constants.
     */
    public const STATE_INITIATED = 'INITIATED';
    public const STATE_MATCHING = 'MATCHING';
    public const STATE_IDENTIFYING_DISCREPANCIES = 'IDENTIFYING_DISCREPANCIES';
    public const STATE_CREATING_ADJUSTMENTS = 'CREATING_ADJUSTMENTS';
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
            new MatchTransactionsStep($logger),
            new IdentifyDiscrepanciesStep($logger),
            new CreateAdjustingEntriesStep($logger),
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
        return 'Cash Reconciliation Workflow';
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
            'bank_account_id',
            'statement_date',
            'statement_ending_balance',
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
            'statement_start_date' => null,
            'statement_start_balance' => null,
            'auto_match_threshold' => 0.01,
            'create_adjustments_automatically' => true,
            'adjustment_account' => '7900',
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
