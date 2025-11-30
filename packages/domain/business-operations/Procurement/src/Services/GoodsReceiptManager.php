<?php

declare(strict_types=1);

namespace Nexus\Procurement\Services;

use Nexus\Procurement\Contracts\GoodsReceiptNoteInterface;
use Nexus\Procurement\Contracts\GoodsReceiptRepositoryInterface;
use Nexus\Procurement\Contracts\PurchaseOrderInterface;
use Nexus\Procurement\Contracts\PurchaseOrderRepositoryInterface;
use Nexus\Procurement\Exceptions\GoodsReceiptNotFoundException;
use Nexus\Procurement\Exceptions\PurchaseOrderNotFoundException;
use Nexus\Procurement\Exceptions\InvalidGoodsReceiptDataException;
use Nexus\Procurement\Exceptions\UnauthorizedApprovalException;
use Psr\Log\LoggerInterface;

/**
 * Manages goods receipt note lifecycle.
 * 
 * Enforces business rules:
 * - GRN quantity cannot exceed PO quantity (BUS-PRO-0110)
 * - PO creator cannot create GRN for same PO (segregation of duties)
 */
final readonly class GoodsReceiptManager
{
    public function __construct(
        private GoodsReceiptRepositoryInterface $repository,
        private PurchaseOrderRepositoryInterface $poRepository,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Create goods receipt note from purchase order.
     *
     * @param string $tenantId
     * @param string $purchaseOrderId
     * @param string $receiverId User receiving the goods
     * @param array{
     *   number: string,
     *   received_date: string,
     *   lines: array<array{po_line_reference: string, quantity_received: float, unit: string, notes?: string}>,
     *   warehouse_location?: string,
     *   notes?: string,
     *   metadata?: array
     * } $data
     * @return GoodsReceiptNoteInterface
     * @throws PurchaseOrderNotFoundException
     * @throws InvalidGoodsReceiptDataException
     * @throws UnauthorizedApprovalException
     */
    public function createGoodsReceipt(
        string $tenantId,
        string $purchaseOrderId,
        string $receiverId,
        array $data
    ): GoodsReceiptNoteInterface {
        $this->validateGoodsReceiptData($data);

        // Get PO and verify it exists
        $purchaseOrder = $this->poRepository->findById($purchaseOrderId);

        if ($purchaseOrder === null) {
            throw PurchaseOrderNotFoundException::forId($purchaseOrderId);
        }

        // Segregation of duties: PO creator cannot create GRN
        if ($purchaseOrder->getCreatorId() === $receiverId) {
            throw UnauthorizedApprovalException::cannotCreateGrnForOwnPo($purchaseOrderId, $receiverId);
        }

        // Validate GRN quantities against PO quantities
        $this->validateGrnQuantitiesAgainstPo($purchaseOrder, $data['lines']);

        $this->logger->info('Creating goods receipt note', [
            'tenant_id' => $tenantId,
            'po_id' => $purchaseOrderId,
            'po_number' => $purchaseOrder->getNumber(),
            'grn_number' => $data['number'],
            'receiver_id' => $receiverId,
            'received_date' => $data['received_date'],
        ]);

        $grn = $this->repository->create($tenantId, $purchaseOrderId, $receiverId, $data);

        $this->logger->info('Goods receipt note created', [
            'tenant_id' => $tenantId,
            'grn_id' => $grn->getId(),
            'grn_number' => $grn->getNumber(),
            'status' => $grn->getStatus(),
        ]);

        return $grn;
    }

    /**
     * Authorize payment for goods receipt.
     *
     * Enforces segregation of duties: GRN creator cannot authorize payment.
     *
     * @param string $grnId
     * @param string $authorizerId
     * @return GoodsReceiptNoteInterface
     * @throws GoodsReceiptNotFoundException
     * @throws UnauthorizedApprovalException
     */
    public function authorizePayment(string $grnId, string $authorizerId): GoodsReceiptNoteInterface
    {
        $grn = $this->repository->findById($grnId);

        if ($grn === null) {
            throw GoodsReceiptNotFoundException::forId($grnId);
        }

        // Segregation of duties: GRN receiver cannot authorize payment
        if ($grn->getReceiverId() === $authorizerId) {
            throw UnauthorizedApprovalException::cannotAuthorizePaymentForOwnGrn($grnId, $authorizerId);
        }

        $this->logger->info('Authorizing payment for GRN', [
            'grn_id' => $grnId,
            'grn_number' => $grn->getNumber(),
            'authorizer_id' => $authorizerId,
        ]);

        $authorizedGrn = $this->repository->authorizePayment($grnId, $authorizerId);

        $this->logger->info('GRN payment authorized', [
            'grn_id' => $grnId,
            'grn_number' => $authorizedGrn->getNumber(),
            'status' => $authorizedGrn->getStatus(),
        ]);

        return $authorizedGrn;
    }

    /**
     * Get goods receipt note by ID.
     *
     * @param string $grnId
     * @return GoodsReceiptNoteInterface
     * @throws GoodsReceiptNotFoundException
     */
    public function getGoodsReceipt(string $grnId): GoodsReceiptNoteInterface
    {
        $grn = $this->repository->findById($grnId);

        if ($grn === null) {
            throw GoodsReceiptNotFoundException::forId($grnId);
        }

        return $grn;
    }

    /**
     * Get all goods receipts for tenant.
     *
     * @param string $tenantId
     * @param array<string, mixed> $filters
     * @return array<GoodsReceiptNoteInterface>
     */
    public function getGoodsReceiptsForTenant(string $tenantId, array $filters = []): array
    {
        return $this->repository->findByTenantId($tenantId, $filters);
    }

    /**
     * Get goods receipts by purchase order.
     *
     * @param string $purchaseOrderId
     * @return array<GoodsReceiptNoteInterface>
     */
    public function getGoodsReceiptsByPo(string $purchaseOrderId): array
    {
        return $this->repository->findByPurchaseOrderId($purchaseOrderId);
    }

    /**
     * Validate goods receipt data.
     *
     * @param array $data
     * @throws InvalidGoodsReceiptDataException
     */
    private function validateGoodsReceiptData(array $data): void
    {
        if (!isset($data['lines']) || !is_array($data['lines']) || count($data['lines']) === 0) {
            throw InvalidGoodsReceiptDataException::noLines();
        }

        if (!isset($data['number'])) {
            throw InvalidGoodsReceiptDataException::missingRequiredField('number');
        }

        if (!isset($data['received_date'])) {
            throw InvalidGoodsReceiptDataException::missingRequiredField('received_date');
        }

        // Validate each line has PO line reference
        foreach ($data['lines'] as $index => $line) {
            if (!isset($line['po_line_reference'])) {
                throw InvalidGoodsReceiptDataException::missingPoLineReference($index + 1);
            }
        }
    }

    /**
     * Validate GRN quantities against PO quantities.
     *
     * Enforces BUS-PRO-0110: GRN quantity cannot exceed PO quantity.
     *
     * @param PurchaseOrderInterface $po
     * @param array<array{po_line_reference: string, quantity_received: float}> $grnLines
     * @throws InvalidGoodsReceiptDataException
     */
    private function validateGrnQuantitiesAgainstPo(PurchaseOrderInterface $po, array $grnLines): void
    {
        $poLines = $po->getLines();

        foreach ($grnLines as $grnLine) {
            $poLineRef = $grnLine['po_line_reference'];
            $grnQty = $grnLine['quantity_received'];

            // Find matching PO line
            $matchingPoLine = null;
            foreach ($poLines as $poLine) {
                if ($poLine->getLineReference() === $poLineRef) {
                    $matchingPoLine = $poLine;
                    break;
                }
            }

            if ($matchingPoLine === null) {
                throw InvalidGoodsReceiptDataException::missingPoLineReference(0);
            }

            $poQty = $matchingPoLine->getQuantity();

            // BUS-PRO-0110: GRN quantity cannot exceed PO quantity
            if ($grnQty > $poQty) {
                throw InvalidGoodsReceiptDataException::quantityExceedsPo($poLineRef, $grnQty, $poQty);
            }
        }
    }
}
