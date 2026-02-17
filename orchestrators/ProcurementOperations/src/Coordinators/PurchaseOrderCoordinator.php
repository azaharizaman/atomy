<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use Nexus\ProcurementOperations\Contracts\PurchaseOrderCoordinatorInterface;
use Nexus\ProcurementOperations\DTOs\CreatePurchaseOrderRequest;
use Nexus\ProcurementOperations\DTOs\PurchaseOrderResult;
use Nexus\ProcurementOperations\Exceptions\PurchaseOrderException;
use Nexus\ProcurementOperations\Exceptions\RequisitionException;
use Nexus\Procurement\Contracts\PurchaseOrderManagerInterface;
use Nexus\Procurement\Contracts\PurchaseOrderQueryInterface;
use Nexus\Procurement\Contracts\RequisitionQueryInterface;
use Nexus\Party\Contracts\VendorQueryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinates the purchase order lifecycle from creation to closure.
 */
final readonly class PurchaseOrderCoordinator implements PurchaseOrderCoordinatorInterface
{
    public function __construct(
        private PurchaseOrderManagerInterface $poManager,
        private PurchaseOrderQueryInterface $poQuery,
        private RequisitionQueryInterface $requisitionQuery,
        private ?VendorQueryInterface $vendorQuery = null,
        private ?EventDispatcherInterface $eventDispatcher = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * @inheritDoc
     */
    public function createFromRequisition(CreatePurchaseOrderRequest $request): PurchaseOrderResult
    {
        $this->logger->info('Creating PO from requisition via coordinator', [
            'tenant_id' => $request->tenantId,
            'requisition_id' => $request->requisitionId,
        ]);

        try {
            // 1. Validate requisition status
            $requisition = $this->requisitionQuery->findById($request->requisitionId);
            if (!$requisition) {
                throw RequisitionException::notFound($request->requisitionId);
            }

            if ($requisition->getStatus()->value !== 'approved') {
                 throw RequisitionException::invalidStatus($request->requisitionId, $requisition->getStatus()->value, 'approved');
            }

            // 2. Validate vendor if possible
            if ($this->vendorQuery !== null) {
                $vendor = $this->vendorQuery->findById($request->vendorId);
                if (!$vendor) {
                    throw PurchaseOrderException::vendorNotFound($request->vendorId);
                }
            }

            // 3. Create PO
            $purchaseOrder = $this->poManager->createFromRequisition(
                requisitionId: $request->requisitionId,
                vendorId: $request->vendorId,
                metadata: array_merge($request->metadata, [
                    'created_by' => $request->createdBy,
                    'payment_terms' => $request->paymentTerms,
                    'delivery_address' => $request->deliveryAddress,
                    'contract_id' => $request->contractId,
                    'currency' => $request->currency,
                    'notes' => $request->notes,
                ])
            );

            return PurchaseOrderResult::success(
                purchaseOrderId: $purchaseOrder->getId(),
                purchaseOrderNumber: $purchaseOrder->getNumber(),
                status: $purchaseOrder->getStatus()->value,
                totalAmountCents: $purchaseOrder->getTotalAmountCents(),
                vendorId: $purchaseOrder->getVendorId(),
                message: 'Purchase order created successfully from requisition.'
            );

        } catch (\Throwable $e) {
            $this->logger->error('Failed to create PO from requisition', [
                'error' => $e->getMessage(),
            ]);
            return PurchaseOrderResult::failure($e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function sendToVendor(string $tenantId, string $purchaseOrderId, string $sentBy, string $deliveryMethod = 'email', ?string $deliveryAddress = null): PurchaseOrderResult
    {
        try {
            $po = $this->poQuery->findById($purchaseOrderId);
            if (!$po) {
                throw PurchaseOrderException::notFound($purchaseOrderId);
            }

            // Update status via manager
            $this->poManager->markAsSent($purchaseOrderId, [
                'sent_by' => $sentBy,
                'method' => $deliveryMethod,
                'address' => $deliveryAddress,
                'sent_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ]);

            // Re-fetch
            $po = $this->poQuery->findById($purchaseOrderId);

            return PurchaseOrderResult::success(
                purchaseOrderId: $purchaseOrderId,
                purchaseOrderNumber: $po->getNumber(),
                status: $po->getStatus()->value,
                totalAmountCents: $po->getTotalAmountCents(),
                vendorId: $po->getVendorId(),
                message: 'Purchase order sent to vendor.'
            );
        } catch (\Throwable $e) {
            return PurchaseOrderResult::failure($e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function amend(string $tenantId, string $purchaseOrderId, array $amendments, string $amendedBy, string $reason): PurchaseOrderResult
    {
        try {
            $po = $this->poQuery->findById($purchaseOrderId);
            if (!$po) {
                throw PurchaseOrderException::notFound($purchaseOrderId);
            }

            $purchaseOrder = $this->poManager->amend($purchaseOrderId, $amendments, [
                'amended_by' => $amendedBy,
                'reason' => $reason,
            ]);

            return PurchaseOrderResult::success(
                purchaseOrderId: $purchaseOrderId,
                purchaseOrderNumber: $purchaseOrder->getNumber(),
                status: $purchaseOrder->getStatus()->value,
                totalAmountCents: $purchaseOrder->getTotalAmountCents(),
                vendorId: $purchaseOrder->getVendorId(),
                message: 'Purchase order amended successfully.'
            );
        } catch (\Throwable $e) {
            return PurchaseOrderResult::failure($e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function cancel(string $tenantId, string $purchaseOrderId, string $cancelledBy, string $reason): PurchaseOrderResult
    {
        try {
            $po = $this->poQuery->findById($purchaseOrderId);
            if (!$po) {
                throw PurchaseOrderException::notFound($purchaseOrderId);
            }

            $this->poManager->cancel($purchaseOrderId, $reason);

            // Re-fetch
            $po = $this->poQuery->findById($purchaseOrderId);

            return PurchaseOrderResult::success(
                purchaseOrderId: $purchaseOrderId,
                purchaseOrderNumber: $po->getNumber(),
                status: $po->getStatus()->value,
                totalAmountCents: $po->getTotalAmountCents(),
                vendorId: $po->getVendorId(),
                message: 'Purchase order cancelled.'
            );
        } catch (\Throwable $e) {
            return PurchaseOrderResult::failure($e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function close(string $tenantId, string $purchaseOrderId, string $closedBy, ?string $reason = null): PurchaseOrderResult
    {
        try {
            $po = $this->poQuery->findById($purchaseOrderId);
            if (!$po) {
                throw PurchaseOrderException::notFound($purchaseOrderId);
            }

            $this->poManager->close($purchaseOrderId, $reason);

            // Re-fetch
            $po = $this->poQuery->findById($purchaseOrderId);

            return PurchaseOrderResult::success(
                purchaseOrderId: $purchaseOrderId,
                purchaseOrderNumber: $po->getNumber(),
                status: $po->getStatus()->value,
                totalAmountCents: $po->getTotalAmountCents(),
                vendorId: $po->getVendorId(),
                message: 'Purchase order closed.'
            );
        } catch (\Throwable $e) {
            return PurchaseOrderResult::failure($e->getMessage());
        }
    }
}
