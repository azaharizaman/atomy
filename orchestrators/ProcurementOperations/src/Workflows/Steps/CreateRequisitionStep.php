<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Workflows\Steps;

use Nexus\ProcurementOperations\Contracts\SagaStepInterface;
use Nexus\ProcurementOperations\DTOs\SagaStepContext;
use Nexus\ProcurementOperations\DTOs\SagaStepResult;
use Nexus\Procurement\Contracts\RequisitionManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Saga step: Create purchase requisition.
 *
 * Forward action: Creates a new requisition.
 * Compensation: Cancels the created requisition.
 */
final readonly class CreateRequisitionStep implements SagaStepInterface
{
    public function __construct(
        private RequisitionManagerInterface $requisitionManager,
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * Get the logger instance, or a NullLogger if none was injected.
     */
    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    public function getId(): string
    {
        return 'create_requisition';
    }

    public function getName(): string
    {
        return 'Create Requisition';
    }

    public function getDescription(): string
    {
        return 'Creates a purchase requisition for required items';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Creating requisition', [
            'saga_instance_id' => $context->sagaInstanceId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            $requisitionData = $context->get('requisition_data', []);

            $requisition = $this->requisitionManager->create(
                tenantId: $context->tenantId,
                requesterId: $context->userId,
                items: $requisitionData['items'] ?? [],
                metadata: [
                    'saga_instance_id' => $context->sagaInstanceId,
                    'department_id' => $requisitionData['department_id'] ?? null,
                    'cost_center_id' => $requisitionData['cost_center_id'] ?? null,
                    'notes' => $requisitionData['notes'] ?? null,
                ],
            );

            return SagaStepResult::success([
                'requisition_id' => $requisition->getId(),
                'requisition_number' => $requisition->getNumber(),
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to create requisition', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                errorMessage: 'Failed to create requisition: ' . $e->getMessage(),
                canRetry: false,
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Compensating: Cancelling requisition', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $requisitionId = $context->getStepOutput('create_requisition', 'requisition_id');

            if ($requisitionId === null) {
                return SagaStepResult::compensated([
                    'message' => 'No requisition to cancel',
                ]);
            }

            $this->requisitionManager->cancel(
                requisitionId: $requisitionId,
                reason: 'Saga compensation - process rolled back',
            );

            return SagaStepResult::compensated([
                'cancelled_requisition_id' => $requisitionId,
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to cancel requisition during compensation', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::compensationFailed(
                'Failed to cancel requisition: ' . $e->getMessage()
            );
        }
    }

    public function hasCompensation(): bool
    {
        return true;
    }

    public function getOrder(): int
    {
        return 1;
    }

    public function isRequired(): bool
    {
        return true;
    }

    public function getTimeout(): int
    {
        return 300; // 5 minutes
    }

    public function getRetryAttempts(): int
    {
        return 3;
    }
}
