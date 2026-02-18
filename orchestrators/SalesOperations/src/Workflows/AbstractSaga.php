<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Workflows;

use Nexus\SalesOperations\Contracts\SagaInterface;
use Nexus\SalesOperations\Contracts\SagaStateInterface;
use Nexus\SalesOperations\Contracts\SagaStepInterface;
use Nexus\SalesOperations\Contracts\SecureIdGeneratorInterface;
use Nexus\SalesOperations\Contracts\WorkflowStorageInterface;
use Nexus\SalesOperations\DTOs\SagaContext;
use Nexus\SalesOperations\DTOs\SagaResult;
use Nexus\SalesOperations\DTOs\SagaStepContext;
use Nexus\SalesOperations\Enums\SagaStatus;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract readonly class AbstractSaga implements SagaInterface
{
    public function __construct(
        protected WorkflowStorageInterface $storage,
        protected EventDispatcherInterface $eventDispatcher,
        protected ?LoggerInterface $logger = null,
        protected ?SecureIdGeneratorInterface $idGenerator = null,
    ) {}

    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

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

    public function getState(string $sagaInstanceId): ?SagaStateInterface
    {
        return $this->storage->loadSagaState($sagaInstanceId);
    }

    public function canExecute(SagaContext $context): bool
    {
        return !empty($context->tenantId) && !empty($context->userId);
    }

    protected function executeStepWithRetry(
        SagaStepInterface $step,
        SagaStepContext $context,
    ): \Nexus\SalesOperations\DTOs\SagaStepResult {
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

            if ($attempts < $maxAttempts) {
                usleep((int) (100000 * (2 ** $attempts)));
            }
        } while ($attempts < $maxAttempts);

        return $result;
    }

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
            }
        }

        return $compensatedSteps;
    }

    protected function saveState(
        string $instanceId,
        SagaStatus $status,
        SagaContext $context,
        array $completedSteps,
        array $compensatedSteps,
    ): void {
        $state = \Nexus\SalesOperations\DTOs\SagaState::create(
            instanceId: $instanceId,
            sagaId: $this->getId(),
            tenantId: $context->tenantId,
            status: $status->value,
            completedSteps: $completedSteps,
            compensatedSteps: $compensatedSteps,
            contextData: $context->data,
            stepData: $context->metadata['step_outputs'] ?? [],
        );

        $this->storage->saveSagaState($state);
    }

    protected function generateInstanceId(): string
    {
        $uniquePart = $this->idGenerator !== null
            ? $this->idGenerator->randomHex(16)
            : bin2hex(random_bytes(16));

        return sprintf(
            '%s-%s',
            $this->getId(),
            $uniquePart
        );
    }
}
