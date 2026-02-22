<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Workflows;

use Nexus\FinanceOperations\Contracts\FinanceWorkflowInterface;
use Nexus\FinanceOperations\Contracts\WorkflowStepInterface;
use Nexus\FinanceOperations\DTOs\WorkflowResult;
use Nexus\FinanceOperations\DTOs\WorkflowStepContext;
use Nexus\FinanceOperations\DTOs\WorkflowStepResult;
use Nexus\FinanceOperations\DTOs\SagaContext;
use Nexus\FinanceOperations\Exceptions\CoordinationException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Abstract base class for finance workflows.
 * 
 * Implements the Saga pattern with:
 * - Sequential step execution
 * - Automatic compensation on failure
 * - State tracking via SagaContext
 * - Comprehensive logging for audit trails
 * 
 * This class follows the orchestrator pattern where:
 * - Coordinators direct flow without executing logic
 * - Workflows manage stateful multi-step processes
 * - Steps are self-contained with compensation support
 *
 * @see FinanceWorkflowInterface
 * @see WorkflowStepInterface
 * @since 1.0.0
 */
abstract readonly class AbstractFinanceWorkflow implements FinanceWorkflowInterface
{
    /**
     * @param WorkflowStepInterface[] $steps Ordered list of workflow steps
     * @param object|null $storage WorkflowStorageInterface implementation for state persistence
     * @param LoggerInterface $logger PSR-3 compliant logger
     * @param array<string, array<string, mixed>> $executionLog Execution log entries
     * @param bool $completed Whether the workflow is complete
     */
    public function __construct(
        protected array $steps = [],
        protected ?object $storage = null,
        protected LoggerInterface $logger = new NullLogger(),
        protected array $executionLog = [],
        protected bool $completed = false,
    ) {}

    /**
     * Get the logger instance, or a NullLogger if none was injected.
     *
     * @return LoggerInterface
     */
    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->getWorkflowName();
    }

    /**
     * @inheritDoc
     */
    public function canStart(array $context): bool
    {
        // Check required context fields
        $requiredFields = $this->getRequiredContextFields();
        
        foreach ($requiredFields as $field) {
            if (!isset($context[$field]) || $context[$field] === null) {
                $this->getLogger()->warning('Workflow cannot start: missing required field', [
                    'field' => $field,
                    'workflow' => static::class,
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $context): WorkflowResult
    {
        $workflowId = $this->generateWorkflowId();
        
        $this->getLogger()->info('Starting workflow execution', [
            'workflow_id' => $workflowId,
            'workflow_class' => static::class,
            'workflow_name' => $this->getWorkflowName(),
        ]);

        $executionLog = [];
        $executionLog[] = $this->createLogEntry('workflow_started', [
            'workflow_id' => $workflowId,
            'context_keys' => array_keys($context),
        ]);

        // Create saga context for state tracking
        $sagaContext = new SagaContext(
            tenantId: $context['tenant_id'] ?? '',
            sagaId: $workflowId,
            data: $context,
            metadata: $context['metadata'] ?? [],
            correlationId: $context['correlation_id'] ?? null,
        );

        $executedSteps = [];
        $stepResults = [];

        try {
            foreach ($this->steps as $step) {
                $stepName = $step->getName();
                
                $this->getLogger()->debug('Executing step', [
                    'workflow_id' => $workflowId,
                    'step' => $stepName,
                ]);

                $executionLog[] = $this->createLogEntry('step_started', [
                    'step' => $stepName,
                ]);

                // Create step context
                $stepContext = new WorkflowStepContext(
                    tenantId: $sagaContext->tenantId,
                    workflowId: $workflowId,
                    stepName: $stepName,
                    data: $sagaContext->data,
                    previousStepResults: $stepResults,
                );

                // Execute the step
                $result = $step->execute($stepContext);

                if (!$result->success) {
                    $this->getLogger()->error('Step failed, starting compensation', [
                        'workflow_id' => $workflowId,
                        'step' => $stepName,
                        'error' => $result->error,
                    ]);

                    $executionLog[] = $this->createLogEntry('step_failed', [
                        'step' => $stepName,
                        'error' => $result->error,
                    ]);

                    // Compensate executed steps in reverse order
                    $this->compensateSteps($executedSteps, $stepContext, $stepResults, $executionLog);

                    return WorkflowResult::failure($workflowId, [
                        'failed_step' => $stepName,
                        'error' => $result->error,
                        'executed_steps' => array_keys($stepResults),
                        'execution_log' => $executionLog,
                    ]);
                }

                $executedSteps[] = $step;
                $stepResults[$stepName] = $result->data;

                $executionLog[] = $this->createLogEntry('step_completed', [
                    'step' => $stepName,
                ]);

                // Update saga context with step output
                $sagaContext = $sagaContext->withStepOutput($stepName, $result->data);
            }

            $this->getLogger()->info('Workflow completed successfully', [
                'workflow_id' => $workflowId,
                'steps_executed' => count($executedSteps),
            ]);

            $executionLog[] = $this->createLogEntry('workflow_completed', [
                'workflow_id' => $workflowId,
                'steps_count' => count($executedSteps),
            ]);

            return WorkflowResult::success($workflowId, [
                'step_results' => $stepResults,
                'saga_context' => $sagaContext,
                'execution_log' => $executionLog,
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Workflow execution failed with exception', [
                'workflow_id' => $workflowId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $executionLog[] = $this->createLogEntry('workflow_exception', [
                'error' => $e->getMessage(),
            ]);

            // Compensate any executed steps
            if (!empty($executedSteps)) {
                $stepContext = new WorkflowStepContext(
                    tenantId: $sagaContext->tenantId,
                    workflowId: $workflowId,
                    stepName: 'compensation',
                    data: $sagaContext->data,
                    previousStepResults: $stepResults,
                );
                $this->compensateSteps($executedSteps, $stepContext, $stepResults, $executionLog);
            }

            return WorkflowResult::failure($workflowId, [
                'exception' => $e->getMessage(),
                'executed_steps' => array_keys($stepResults),
                'execution_log' => $executionLog,
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    public function compensate(WorkflowResult $result): void
    {
        $this->getLogger()->info('Compensating workflow', [
            'workflow_id' => $result->workflowId,
            'success' => $result->success,
        ]);

        // Reconstruct context and compensate all steps
        $stepResults = $result->stepResults;
        
        $context = new WorkflowStepContext(
            tenantId: $result->data['tenant_id'] ?? '',
            workflowId: $result->workflowId,
            stepName: 'manual_compensation',
            data: $result->data,
            previousStepResults: $stepResults,
        );

        $executionLog = $result->data['execution_log'] ?? [];
        $this->compensateSteps($this->steps, $context, $stepResults, $executionLog);
    }

    /**
     * @inheritDoc
     */
    public function getCurrentStep(): ?string
    {
        // Would be implemented with state storage
        return null;
    }

    /**
     * @inheritDoc
     */
    public function canRetry(): bool
    {
        return true; // Default to allowing retry
    }

    /**
     * @inheritDoc
     */
    public function isComplete(): bool
    {
        return $this->completed;
    }

    /**
     * @inheritDoc
     */
    public function getExecutionLog(): array
    {
        return $this->executionLog;
    }

    /**
     * Compensate executed steps in reverse order.
     * 
     * This method implements the Saga pattern's compensation logic,
     * rolling back completed steps when a failure occurs.
     *
     * @param WorkflowStepInterface[] $steps Steps to compensate
     * @param WorkflowStepContext $context The execution context
     * @param array<string, array<string, mixed>> $stepResults Results from executed steps
     * @param array<int, array<string, mixed>> $executionLog Execution log to append to
     */
    protected function compensateSteps(array $steps, WorkflowStepContext $context, array $stepResults, array &$executionLog): void
    {
        $reversedSteps = array_reverse($steps);

        foreach ($reversedSteps as $step) {
            try {
                $stepName = $step->getName();
                
                $this->getLogger()->info('Compensating step', [
                    'workflow_id' => $context->workflowId,
                    'step' => $stepName,
                ]);

                $executionLog[] = $this->createLogEntry('step_compensation_started', [
                    'step' => $stepName,
                ]);

                // Create context with previous step results for compensation
                $compensateContext = new WorkflowStepContext(
                    tenantId: $context->tenantId,
                    workflowId: $context->workflowId,
                    stepName: $stepName,
                    data: $context->data,
                    previousStepResults: $stepResults,
                );

                $step->compensate($compensateContext);

                $executionLog[] = $this->createLogEntry('step_compensated', [
                    'step' => $stepName,
                ]);
            } catch (\Throwable $e) {
                $this->getLogger()->error('Compensation failed for step', [
                    'workflow_id' => $context->workflowId,
                    'step' => $step->getName(),
                    'error' => $e->getMessage(),
                ]);

                $executionLog[] = $this->createLogEntry('step_compensation_failed', [
                    'step' => $step->getName(),
                    'error' => $e->getMessage(),
                ]);
                // Continue compensating other steps even if one fails
            }
        }
    }

    /**
     * Generate a unique workflow ID.
     *
     * @return string Unique workflow identifier
     */
    protected function generateWorkflowId(): string
    {
        return 'WF-' . str_replace('\\', '-', static::class) . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
    }

    /**
     * Create a log entry.
     *
     * @param string $action The action being logged
     * @param array<string, mixed> $data Additional data for the log entry
     * @return array<string, mixed> The log entry
     */
    protected function createLogEntry(string $action, array $data = []): array
    {
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => $action,
            'data' => $data,
        ];
    }

    /**
     * Get the workflow name for identification and logging.
     *
     * @return string Human-readable workflow name
     */
    abstract public function getWorkflowName(): string;

    /**
     * Get required context fields for this workflow.
     *
     * @return array<string> List of required field names
     */
    abstract public function getRequiredContextFields(): array;

    /**
     * Get all workflow steps.
     *
     * @return WorkflowStepInterface[] List of workflow steps
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * Get the total number of steps in this workflow.
     *
     * @return int Number of steps
     */
    public function getTotalSteps(): int
    {
        return count($this->steps);
    }
}
