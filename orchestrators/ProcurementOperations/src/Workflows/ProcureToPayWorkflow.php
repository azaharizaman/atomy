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
use Nexus\ProcurementOperations\Enums\P2PStep;
use Nexus\ProcurementOperations\Events\P2PWorkflowStartedEvent;
use Nexus\ProcurementOperations\Events\P2PWorkflowCompletedEvent;
use Nexus\ProcurementOperations\Events\P2PWorkflowFailedEvent;
use Nexus\ProcurementOperations\Events\P2PStepCompletedEvent;
use Nexus\ProcurementOperations\Workflows\Steps\CreateRequisitionStep;
use Nexus\ProcurementOperations\Workflows\Steps\CreatePurchaseOrderStep;
use Nexus\ProcurementOperations\Workflows\Steps\ReceiveGoodsStep;
use Nexus\ProcurementOperations\Workflows\Steps\ThreeWayMatchStep;
use Nexus\ProcurementOperations\Workflows\Steps\CreateAccrualStep;
use Nexus\ProcurementOperations\Workflows\Steps\ProcessPaymentStep;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Procure-to-Pay (P2P) Workflow Saga.
 *
 * Orchestrates the complete procurement lifecycle:
 * 1. Create Requisition
 * 2. Approve Requisition
 * 3. Create Purchase Order
 * 4. Approve Purchase Order
 * 5. Send PO to Vendor
 * 6. Receive Goods
 * 7. 3-Way Matching (PO, GR, Invoice)
 * 8. Create Accrual (if needed)
 * 9. Process Payment
 * 10. Reverse Accrual (when invoice received)
 *
 * Implements Saga pattern with compensation for distributed transaction handling.
 */
final class ProcureToPayWorkflow extends AbstractSaga implements SagaInterface
{
    private string $id;

    /**
     * @param array<SagaStepInterface> $steps
     */
    public function __construct(
        private readonly array $steps,
        WorkflowStorageInterface $storage,
        EventDispatcherInterface $eventDispatcher,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($storage, $eventDispatcher, $logger ?? new NullLogger());
        $this->id = 'procure_to_pay_workflow';
    }

    /**
     * Create workflow with default steps.
     */
    public static function create(
        CreateRequisitionStep $createRequisition,
        CreatePurchaseOrderStep $createPurchaseOrder,
        ReceiveGoodsStep $receiveGoods,
        ThreeWayMatchStep $threeWayMatch,
        CreateAccrualStep $createAccrual,
        ProcessPaymentStep $processPayment,
        WorkflowStorageInterface $storage,
        EventDispatcherInterface $eventDispatcher,
        ?LoggerInterface $logger = null,
    ): self {
        return new self(
            steps: [
                $createRequisition,
                $createPurchaseOrder,
                $receiveGoods,
                $threeWayMatch,
                $createAccrual,
                $processPayment,
            ],
            storage: $storage,
            eventDispatcher: $eventDispatcher,
            logger: $logger,
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return 'Procure-to-Pay Workflow';
    }

    public function getDescription(): string
    {
        return 'Complete procurement lifecycle from requisition to payment with saga compensation';
    }

    /**
     * @return array<SagaStepInterface>
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    public function canExecute(SagaContext $context): bool
    {
        // Verify required context data
        if (!$context->has('requisition_data') && !$context->has('requisition_id')) {
            return false;
        }

        // At minimum, we need vendor information to create PO
        if (!$context->has('purchase_order_data')) {
            $requisitionData = $context->get('requisition_data', []);
            if (empty($requisitionData['vendor_id'] ?? null)) {
                return false;
            }
        }

        return true;
    }

    public function execute(SagaContext $context): SagaResult
    {
        $instanceId = $this->generateInstanceId();

        $this->logger->info('Starting Procure-to-Pay workflow', [
            'instance_id' => $instanceId,
            'tenant_id' => $context->tenantId,
            'user_id' => $context->userId,
        ]);

        // Dispatch start event
        $this->eventDispatcher->dispatch(new P2PWorkflowStartedEvent(
            instanceId: $instanceId,
            tenantId: $context->tenantId,
            userId: $context->userId,
            data: $context->data,
        ));

        // Execute using parent saga logic
        $result = parent::execute($context->withInstanceId($instanceId));

        // Dispatch completion or failure event
        if ($result->status === SagaStatus::COMPLETED) {
            $this->eventDispatcher->dispatch(new P2PWorkflowCompletedEvent(
                instanceId: $instanceId,
                tenantId: $context->tenantId,
                completedSteps: $result->completedSteps,
                outputs: $result->data,
            ));
        } elseif ($result->status->isFailed()) {
            $this->eventDispatcher->dispatch(new P2PWorkflowFailedEvent(
                instanceId: $instanceId,
                tenantId: $context->tenantId,
                failedStep: $result->failedStep,
                error: $result->errorMessage ?? 'Unknown error',
                compensatedSteps: $result->compensatedSteps,
            ));
        }

        return $result;
    }

    /**
     * Execute workflow from a specific step (for resumption).
     */
    public function resumeFrom(SagaContext $context, string $stepId): SagaResult
    {
        $this->logger->info('Resuming Procure-to-Pay workflow', [
            'instance_id' => $context->sagaInstanceId,
            'from_step' => $stepId,
        ]);

        // Load existing state
        $state = $this->storage->loadSagaState($this->getId(), $context->sagaInstanceId);

        if ($state === null) {
            return SagaResult::failure(
                status: SagaStatus::FAILED,
                completedSteps: [],
                failedStep: $stepId,
                errorMessage: 'Cannot resume: saga state not found',
            );
        }

        // Find steps to execute from resume point
        $stepsToExecute = [];
        $foundStartStep = false;

        foreach ($this->getStepsSorted() as $step) {
            if ($step->getId() === $stepId) {
                $foundStartStep = true;
            }

            if ($foundStartStep && !in_array($step->getId(), $state->getCompletedSteps(), true)) {
                $stepsToExecute[] = $step;
            }
        }

        if (empty($stepsToExecute)) {
            return SagaResult::success(
                status: SagaStatus::COMPLETED,
                completedSteps: $state->getCompletedSteps(),
                data: $state->getData(),
            );
        }

        // Execute remaining steps
        return $this->executeSteps($context, $stepsToExecute, $state->getCompletedSteps());
    }

    /**
     * Create a partial P2P workflow for specific scenarios.
     */
    public static function createPartial(
        array $steps,
        WorkflowStorageInterface $storage,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger = new NullLogger(),
    ): self {
        return new self(
            steps: $steps,
            storage: $storage,
            eventDispatcher: $eventDispatcher,
            logger: $logger,
        );
    }

    /**
     * Get available workflow variants.
     *
     * @return array<string, string>
     */
    public static function getVariants(): array
    {
        return [
            'full' => 'Complete P2P cycle from requisition to payment',
            'quick_po' => 'Skip requisition, start from PO (for urgent purchases)',
            'gr_to_payment' => 'From goods receipt to payment (GR already exists)',
            'matching_only' => 'Only perform 3-way matching and payment',
        ];
    }

    /**
     * Get P2P step details.
     *
     * @return array<string, array{step: P2PStep, description: string, compensatable: bool}>
     */
    public function getStepDetails(): array
    {
        $details = [];

        foreach (P2PStep::cases() as $step) {
            $details[$step->value] = [
                'step' => $step,
                'description' => $step->description(),
                'compensatable' => $step->hasCompensation(),
            ];
        }

        return $details;
    }

    /**
     * Execute specific steps with state tracking.
     *
     * @param array<SagaStepInterface> $steps
     * @param array<string> $alreadyCompleted
     */
    private function executeSteps(SagaContext $context, array $steps, array $alreadyCompleted = []): SagaResult
    {
        $completedSteps = $alreadyCompleted;
        $stepOutputs = $context->stepOutputs;

        foreach ($steps as $step) {
            $stepContext = new SagaStepContext(
                sagaInstanceId: $context->sagaInstanceId,
                tenantId: $context->tenantId,
                userId: $context->userId,
                correlationId: $context->correlationId,
                data: $context->data,
                metadata: $context->metadata,
                isCompensation: false,
                stepOutputs: $stepOutputs,
            );

            $result = $this->executeStepWithRetry($step, $stepContext);

            if (!$result->isSuccessful()) {
                // Step failed, trigger compensation
                $this->logger->error('P2P step failed, initiating compensation', [
                    'failed_step' => $step->getId(),
                    'error' => $result->errorMessage,
                ]);

                $compensationContext = $stepContext->forCompensation($stepOutputs);
                $compensationResult = $this->compensateSteps(
                    array_reverse($this->getCompletedStepObjects($completedSteps)),
                    $compensationContext
                );

                return SagaResult::failure(
                    status: $compensationResult->status,
                    completedSteps: $completedSteps,
                    compensatedSteps: $compensationResult->compensatedSteps,
                    failedStep: $step->getId(),
                    errorMessage: $result->errorMessage,
                );
            }

            // Track completed step
            $completedSteps[] = $step->getId();
            $stepOutputs[$step->getId()] = $result->data;

            // Dispatch step completed event
            $this->eventDispatcher->dispatch(new P2PStepCompletedEvent(
                instanceId: $context->sagaInstanceId,
                stepId: $step->getId(),
                stepName: $step->getName(),
                output: $result->data,
            ));
        }

        return SagaResult::success(
            status: SagaStatus::COMPLETED,
            completedSteps: $completedSteps,
            data: $stepOutputs,
        );
    }

    /**
     * Get step objects for completed step IDs.
     *
     * @param array<string> $stepIds
     * @return array<SagaStepInterface>
     */
    private function getCompletedStepObjects(array $stepIds): array
    {
        $stepMap = [];
        foreach ($this->steps as $step) {
            $stepMap[$step->getId()] = $step;
        }

        $objects = [];
        foreach ($stepIds as $stepId) {
            if (isset($stepMap[$stepId])) {
                $objects[] = $stepMap[$stepId];
            }
        }

        return $objects;
    }

    /**
     * Get steps sorted by order.
     *
     * @return array<SagaStepInterface>
     */
    private function getStepsSorted(): array
    {
        $sorted = $this->steps;
        usort($sorted, fn($a, $b) => $a->getOrder() <=> $b->getOrder());

        return $sorted;
    }
}
