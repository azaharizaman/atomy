<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Workflows;

use Nexus\ProcurementOperations\Contracts\SagaInterface;
use Nexus\ProcurementOperations\Contracts\SagaStateInterface;
use Nexus\ProcurementOperations\Contracts\SagaStepInterface;
use Nexus\ProcurementOperations\Contracts\WorkflowStorageInterface;
use Nexus\ProcurementOperations\DTOs\SagaContext;
use Nexus\ProcurementOperations\DTOs\SagaResult;
use Nexus\ProcurementOperations\DTOs\SagaStepContext;
use Nexus\ProcurementOperations\Enums\SagaStatus;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Abstract base class for Saga implementations.
 *
 * Provides common saga execution logic including:
 * - Step execution with retry logic
 * - Compensation (rollback) on failure
 * - State persistence
 * - Event dispatching
 */
abstract readonly class AbstractSaga implements SagaInterface
{
    public function __construct(
        protected WorkflowStorageInterface $storage,
        protected EventDispatcherInterface $eventDispatcher,
        protected LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Get the logger instance, or a NullLogger if none was injected.
     */
    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    /**
     * Execute the saga with given context.
     */
    public function execute(SagaContext $context): SagaResult
    {
        $instanceId = $this->generateInstanceId();
        $steps = $this->getSteps();
        $completedSteps = [];
        $stepOutputs = [];

        $this->getLogger()->info('Starting saga execution', [
            'saga_id' => $this->getId(),
            'instance_id' => $instanceId,
            'tenant_id' => $context->tenantId,
        ]);

        // Save initial state
        $this->saveState($instanceId, SagaStatus::EXECUTING, $context, [], []);

        try {
            foreach ($steps as $step) {
                $stepContext = new SagaStepContext(
                    tenantId: $context->tenantId,
                    userId: $context->userId,
                    sagaInstanceId: $instanceId,
                    stepId: $step->getId(),
                    data: array_merge($context->data, ['step_outputs' => $stepOutputs]),
                    stepOutputs: $stepOutputs,
                    metadata: $context->metadata,
                    isCompensation: false,
                );

                $this->getLogger()->debug('Executing saga step', [
                    'step_id' => $step->getId(),
                    'step_name' => $step->getName(),
                ]);

                $result = $this->executeStepWithRetry($step, $stepContext);

                if (!$result->success) {
                    $this->getLogger()->warning('Saga step failed, initiating compensation', [
                        'step_id' => $step->getId(),
                        'error' => $result->errorMessage,
                    ]);

                    // Trigger compensation for completed steps
                    $compensatedSteps = $this->compensateSteps(
                        $instanceId,
                        $completedSteps,
                        $context,
                        $stepOutputs
                    );

                    $this->saveState(
                        $instanceId,
                        count($compensatedSteps) === count($completedSteps)
                            ? SagaStatus::COMPENSATED
                            : SagaStatus::COMPENSATION_FAILED,
                        $context,
                        $completedSteps,
                        $compensatedSteps
                    );

                    return SagaResult::failedWithCompensation(
                        instanceId: $instanceId,
                        sagaId: $this->getId(),
                        failedStep: $step->getId(),
                        errorMessage: $result->errorMessage ?? 'Step execution failed',
                        completedSteps: $completedSteps,
                        compensatedSteps: $compensatedSteps,
                        compensationSucceeded: count($compensatedSteps) === count($completedSteps),
                    );
                }

                $completedSteps[] = $step->getId();
                $stepOutputs[$step->getId()] = $result->data;
            }

            // All steps completed successfully
            $this->saveState($instanceId, SagaStatus::COMPLETED, $context, $completedSteps, []);

            $this->getLogger()->info('Saga completed successfully', [
                'saga_id' => $this->getId(),
                'instance_id' => $instanceId,
            ]);

            return SagaResult::success(
                instanceId: $instanceId,
                sagaId: $this->getId(),
                completedSteps: $completedSteps,
                data: $stepOutputs,
            );
        } catch (\Throwable $e) {
            $this->getLogger()->error('Saga execution failed with exception', [
                'saga_id' => $this->getId(),
                'instance_id' => $instanceId,
                'error' => $e->getMessage(),
            ]);

            // Attempt compensation
            $compensatedSteps = $this->compensateSteps(
                $instanceId,
                $completedSteps,
                $context,
                $stepOutputs
            );

            $this->saveState(
                $instanceId,
                SagaStatus::COMPENSATION_FAILED,
                $context,
                $completedSteps,
                $compensatedSteps
            );

            return SagaResult::failedWithCompensation(
                instanceId: $instanceId,
                sagaId: $this->getId(),
                failedStep: 'unknown',
                errorMessage: $e->getMessage(),
                completedSteps: $completedSteps,
                compensatedSteps: $compensatedSteps,
                compensationSucceeded: false,
            );
        }
    }

    /**
     * Manually trigger compensation for a saga instance.
     */
    public function compensate(string $sagaInstanceId, ?string $reason = null): SagaResult
    {
        $state = $this->getState($sagaInstanceId);

        if ($state === null) {
            return SagaResult::failed(
                instanceId: $sagaInstanceId,
                sagaId: $this->getId(),
                failedStep: 'unknown',
                errorMessage: 'Saga instance not found',
            );
        }

        $this->getLogger()->info('Manual compensation triggered', [
            'saga_id' => $this->getId(),
            'instance_id' => $sagaInstanceId,
            'reason' => $reason,
        ]);

        $context = new SagaContext(
            tenantId: $state->getTenantId(),
            userId: 'system',
            data: $state->getContextData(),
        );

        $compensatedSteps = $this->compensateSteps(
            $sagaInstanceId,
            $state->getCompletedSteps(),
            $context,
            $state->getStepData()
        );

        return SagaResult::failedWithCompensation(
            instanceId: $sagaInstanceId,
            sagaId: $this->getId(),
            failedStep: 'manual_compensation',
            errorMessage: $reason ?? 'Manual compensation',
            completedSteps: $state->getCompletedSteps(),
            compensatedSteps: $compensatedSteps,
            compensationSucceeded: count($compensatedSteps) === count($state->getCompletedSteps()),
        );
    }

    /**
     * Get the current state of a saga instance.
     */
    public function getState(string $sagaInstanceId): ?SagaStateInterface
    {
        return $this->storage->loadSagaState($sagaInstanceId);
    }

    /**
     * Check if saga can be executed with given context.
     */
    public function canExecute(SagaContext $context): bool
    {
        // Default implementation: check required data fields
        return !empty($context->tenantId) && !empty($context->userId);
    }

    /**
     * Execute a step with retry logic.
     */
    protected function executeStepWithRetry(
        SagaStepInterface $step,
        SagaStepContext $context,
    ): \Nexus\ProcurementOperations\DTOs\SagaStepResult {
        $attempts = 0;
        $maxAttempts = $step->getRetryAttempts();

        do {
            $result = $step->execute($context);

            if ($result->success || !$result->canRetry) {
                return $result;
            }

            $attempts++;
            $this->getLogger()->debug('Retrying saga step', [
                'step_id' => $step->getId(),
                'attempt' => $attempts,
                'max_attempts' => $maxAttempts,
            ]);

            // Exponential backoff
            if ($attempts < $maxAttempts) {
                usleep((int) (100000 * (2 ** $attempts))); // 0.1s, 0.2s, 0.4s, etc.
            }
        } while ($attempts < $maxAttempts);

        return $result;
    }

    /**
     * Compensate completed steps in reverse order.
     *
     * @param string $instanceId Saga instance ID
     * @param array<string> $completedSteps Steps to compensate
     * @param SagaContext $context Original context
     * @param array<string, array<string, mixed>> $stepOutputs Outputs from steps
     * @return array<string> Steps that were successfully compensated
     */
    protected function compensateSteps(
        string $instanceId,
        array $completedSteps,
        SagaContext $context,
        array $stepOutputs,
    ): array {
        $compensatedSteps = [];
        $steps = $this->getSteps();
        $stepsById = [];

        foreach ($steps as $step) {
            $stepsById[$step->getId()] = $step;
        }

        // Compensate in reverse order
        $reversedSteps = array_reverse($completedSteps);

        foreach ($reversedSteps as $stepId) {
            $step = $stepsById[$stepId] ?? null;

            if ($step === null || !$step->hasCompensation()) {
                continue;
            }

            $stepContext = new SagaStepContext(
                tenantId: $context->tenantId,
                userId: $context->userId,
                sagaInstanceId: $instanceId,
                stepId: $stepId,
                data: array_merge($context->data, ['step_outputs' => $stepOutputs]),
                stepOutputs: $stepOutputs,
                metadata: $context->metadata,
                isCompensation: true,
            );

            $this->getLogger()->debug('Compensating saga step', [
                'step_id' => $stepId,
                'step_name' => $step->getName(),
            ]);

            $result = $step->compensate($stepContext);

            if ($result->success) {
                $compensatedSteps[] = $stepId;
            } else {
                $this->getLogger()->error('Step compensation failed', [
                    'step_id' => $stepId,
                    'error' => $result->errorMessage,
                ]);
                // Continue with other compensations even if one fails
            }
        }

        return $compensatedSteps;
    }

    /**
     * Save saga state to storage.
     *
     * @param string $instanceId Saga instance ID
     * @param SagaStatus $status Current status
     * @param SagaContext $context Saga context
     * @param array<string> $completedSteps Completed steps
     * @param array<string> $compensatedSteps Compensated steps
     */
    protected function saveState(
        string $instanceId,
        SagaStatus $status,
        SagaContext $context,
        array $completedSteps,
        array $compensatedSteps,
    ): void {
        // State persistence is delegated to consumer implementation
        // This method can be overridden for custom persistence logic
    }

    /**
     * Generate a unique instance ID.
     */
    protected function generateInstanceId(): string
    {
        return sprintf(
            '%s-%s',
            $this->getId(),
            bin2hex(random_bytes(16))
        );
    }
}
