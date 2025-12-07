<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Workflows\Steps;

use Nexus\ProcurementOperations\Contracts\SagaStepInterface;
use Nexus\ProcurementOperations\DTOs\SagaStepContext;
use Nexus\ProcurementOperations\DTOs\SagaStepResult;
use Nexus\Procurement\Contracts\PurchaseOrderManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Saga step: Create purchase order from approved requisition.
 *
 * Forward action: Creates a PO from requisition.
 * Compensation: Cancels the created PO.
 */
final readonly class CreatePurchaseOrderStep implements SagaStepInterface
{
    public function __construct(
        private PurchaseOrderManagerInterface $poManager,
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
        return 'create_purchase_order';
    }

    public function getName(): string
    {
        return 'Create Purchase Order';
    }

    public function getDescription(): string
    {
        return 'Creates a purchase order from approved requisition';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Creating purchase order', [
            'saga_instance_id' => $context->sagaInstanceId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            // Get requisition ID from previous step or context
            $requisitionId = $context->getStepOutput('approve_requisition', 'requisition_id')
                ?? $context->getStepOutput('create_requisition', 'requisition_id')
                ?? $context->get('requisition_id');

            $poData = $context->get('purchase_order_data', []);

            $purchaseOrder = $this->poManager->createFromRequisition(
                requisitionId: $requisitionId,
                vendorId: $poData['vendor_id'],
                metadata: [
                    'saga_instance_id' => $context->sagaInstanceId,
                    'payment_terms' => $poData['payment_terms'] ?? null,
                    'delivery_address_id' => $poData['delivery_address_id'] ?? null,
                    'expected_delivery_date' => $poData['expected_delivery_date'] ?? null,
                ],
            );

            return SagaStepResult::success([
                'purchase_order_id' => $purchaseOrder->getId(),
                'purchase_order_number' => $purchaseOrder->getNumber(),
                'vendor_id' => $purchaseOrder->getVendorId(),
                'total_amount' => $purchaseOrder->getTotalAmount(),
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to create purchase order', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                errorMessage: 'Failed to create purchase order: ' . $e->getMessage(),
                canRetry: false,
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Compensating: Cancelling purchase order', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $poId = $context->getStepOutput('create_purchase_order', 'purchase_order_id');

            if ($poId === null) {
                return SagaStepResult::compensated([
                    'message' => 'No purchase order to cancel',
                ]);
            }

            $this->poManager->cancel(
                purchaseOrderId: $poId,
                reason: 'Saga compensation - process rolled back',
            );

            return SagaStepResult::compensated([
                'cancelled_po_id' => $poId,
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to cancel PO during compensation', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::compensationFailed(
                'Failed to cancel purchase order: ' . $e->getMessage()
            );
        }
    }

    public function hasCompensation(): bool
    {
        return true;
    }

    public function getOrder(): int
    {
        return 3;
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
