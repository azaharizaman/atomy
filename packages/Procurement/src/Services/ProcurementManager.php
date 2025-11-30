<?php

declare(strict_types=1);

namespace Nexus\Procurement\Services;

use Nexus\Procurement\Contracts\ProcurementManagerInterface;
use Nexus\Procurement\Contracts\RequisitionInterface;
use Nexus\Procurement\Contracts\PurchaseOrderInterface;
use Nexus\Procurement\Contracts\GoodsReceiptNoteInterface;
use Nexus\Procurement\Contracts\VendorQuoteInterface;
use Nexus\Procurement\Contracts\PurchaseOrderLineInterface;
use Nexus\Procurement\Contracts\GoodsReceiptLineInterface;
use Psr\Log\LoggerInterface;

/**
 * Main procurement orchestrator service.
 * 
 * Implements the full procurement workflow:
 * 1. Requisition creation → Approval → PO conversion
 * 2. Vendor quotes (optional RFQ process)
 * 3. Goods receipt → Three-way matching
 * 
 * This is the primary service consumed by Nexus\Atomy.
 */
final readonly class ProcurementManager implements ProcurementManagerInterface
{
    public function __construct(
        private RequisitionManager $requisitionManager,
        private PurchaseOrderManager $purchaseOrderManager,
        private GoodsReceiptManager $goodsReceiptManager,
        private VendorQuoteManager $vendorQuoteManager,
        private MatchingEngine $matchingEngine,
        private LoggerInterface $logger
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function createRequisition(string $tenantId, string $requesterId, array $data): RequisitionInterface
    {
        $this->logger->info('ProcurementManager: Creating requisition', [
            'tenant_id' => $tenantId,
            'requester_id' => $requesterId,
        ]);

        return $this->requisitionManager->createRequisition($tenantId, $requesterId, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function submitRequisitionForApproval(string $requisitionId): RequisitionInterface
    {
        $this->logger->info('ProcurementManager: Submitting requisition for approval', [
            'requisition_id' => $requisitionId,
        ]);

        return $this->requisitionManager->submitForApproval($requisitionId);
    }

    /**
     * {@inheritdoc}
     */
    public function approveRequisition(string $requisitionId, string $approverId): RequisitionInterface
    {
        $this->logger->info('ProcurementManager: Approving requisition', [
            'requisition_id' => $requisitionId,
            'approver_id' => $approverId,
        ]);

        return $this->requisitionManager->approveRequisition($requisitionId, $approverId);
    }

    /**
     * {@inheritdoc}
     */
    public function rejectRequisition(string $requisitionId, string $rejectorId, string $reason): RequisitionInterface
    {
        $this->logger->info('ProcurementManager: Rejecting requisition', [
            'requisition_id' => $requisitionId,
            'rejector_id' => $rejectorId,
            'reason' => $reason,
        ]);

        return $this->requisitionManager->rejectRequisition($requisitionId, $rejectorId, $reason);
    }

    /**
     * {@inheritdoc}
     */
    public function convertRequisitionToPo(
        string $tenantId,
        string $requisitionId,
        string $creatorId,
        array $poData
    ): PurchaseOrderInterface {
        $this->logger->info('ProcurementManager: Converting requisition to PO', [
            'tenant_id' => $tenantId,
            'requisition_id' => $requisitionId,
            'creator_id' => $creatorId,
        ]);

        return $this->purchaseOrderManager->createFromRequisition(
            $tenantId,
            $requisitionId,
            $creatorId,
            $poData
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createDirectPO(string $tenantId, string $creatorId, array $data): PurchaseOrderInterface
    {
        $this->logger->info('ProcurementManager: Creating direct purchase order', [
            'tenant_id' => $tenantId,
            'creator_id' => $creatorId,
            'po_number' => $data['number'] ?? 'N/A',
        ]);

        // For direct PO creation without requisition, we create a blanket PO
        return $this->purchaseOrderManager->createBlanketPo($tenantId, $creatorId, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function releasePO(string $poId, string $releasedBy): PurchaseOrderInterface
    {
        $this->logger->info('ProcurementManager: Releasing purchase order', [
            'po_id' => $poId,
            'released_by' => $releasedBy,
        ]);

        // Implementation would update PO status to 'released'
        // For now, return the PO (assuming status update happens in repository)
        return $this->purchaseOrderManager->getPurchaseOrder($poId);
    }

    /**
     * {@inheritdoc}
     */
    public function recordGoodsReceipt(
        string $tenantId,
        string $poId,
        string $receiverId,
        array $receiptData
    ): GoodsReceiptNoteInterface {
        $this->logger->info('ProcurementManager: Recording goods receipt', [
            'tenant_id' => $tenantId,
            'po_id' => $poId,
            'receiver_id' => $receiverId,
        ]);

        return $this->goodsReceiptManager->createGoodsReceipt(
            $tenantId,
            $poId,
            $receiverId,
            $receiptData
        );
    }

    /**
     * {@inheritdoc}
     */
    public function performThreeWayMatch(
        PurchaseOrderLineInterface $poLine,
        GoodsReceiptLineInterface $grnLine,
        array $invoiceLineData
    ): array {
        $this->logger->info('ProcurementManager: Performing three-way match', [
            'po_line_ref' => $poLine->getLineReference(),
            'grn_line_ref' => $grnLine->getPoLineReference(),
        ]);

        return $this->matchingEngine->performThreeWayMatch($poLine, $grnLine, $invoiceLineData);
    }

    /**
     * {@inheritdoc}
     */
    public function getRequisition(string $requisitionId): RequisitionInterface
    {
        return $this->requisitionManager->getRequisition($requisitionId);
    }

    /**
     * {@inheritdoc}
     */
    public function getPurchaseOrder(string $poId): PurchaseOrderInterface
    {
        return $this->purchaseOrderManager->getPurchaseOrder($poId);
    }

    /**
     * {@inheritdoc}
     */
    public function getGoodsReceipt(string $grnId): GoodsReceiptNoteInterface
    {
        return $this->goodsReceiptManager->getGoodsReceipt($grnId);
    }

    /**
     * Create vendor quote for requisition (RFQ process).
     *
     * @param string $tenantId
     * @param string $requisitionId
     * @param array $quoteData
     * @return VendorQuoteInterface
     */
    public function createVendorQuote(string $tenantId, string $requisitionId, array $quoteData): VendorQuoteInterface
    {
        $this->logger->info('ProcurementManager: Creating vendor quote', [
            'tenant_id' => $tenantId,
            'requisition_id' => $requisitionId,
            'rfq_number' => $quoteData['rfq_number'] ?? 'N/A',
        ]);

        return $this->vendorQuoteManager->createQuote($tenantId, $requisitionId, $quoteData);
    }

    /**
     * Compare vendor quotes for requisition.
     *
     * @param string $requisitionId
     * @return array Quote comparison matrix
     */
    public function compareVendorQuotes(string $requisitionId): array
    {
        $this->logger->info('ProcurementManager: Comparing vendor quotes', [
            'requisition_id' => $requisitionId,
        ]);

        return $this->vendorQuoteManager->compareQuotes($requisitionId);
    }

    /**
     * Accept vendor quote.
     *
     * @param string $quoteId
     * @param string $acceptorId
     * @return VendorQuoteInterface
     */
    public function acceptVendorQuote(string $quoteId, string $acceptorId): VendorQuoteInterface
    {
        $this->logger->info('ProcurementManager: Accepting vendor quote', [
            'quote_id' => $quoteId,
            'acceptor_id' => $acceptorId,
        ]);

        return $this->vendorQuoteManager->acceptQuote($quoteId, $acceptorId);
    }

    /**
     * Authorize payment for goods receipt.
     *
     * @param string $grnId
     * @param string $authorizerId
     * @return GoodsReceiptNoteInterface
     */
    public function authorizeGrnPayment(string $grnId, string $authorizerId): GoodsReceiptNoteInterface
    {
        $this->logger->info('ProcurementManager: Authorizing GRN payment', [
            'grn_id' => $grnId,
            'authorizer_id' => $authorizerId,
        ]);

        return $this->goodsReceiptManager->authorizePayment($grnId, $authorizerId);
    }

    /**
     * Get all requisitions for tenant.
     *
     * @param string $tenantId
     * @param array<string, mixed> $filters
     * @return array<RequisitionInterface>
     */
    public function getRequisitionsForTenant(string $tenantId, array $filters = []): array
    {
        return $this->requisitionManager->getRequisitionsForTenant($tenantId, $filters);
    }

    /**
     * Get all purchase orders for tenant.
     *
     * @param string $tenantId
     * @param array<string, mixed> $filters
     * @return array<PurchaseOrderInterface>
     */
    public function getPurchaseOrdersForTenant(string $tenantId, array $filters = []): array
    {
        return $this->purchaseOrderManager->getPurchaseOrdersForTenant($tenantId, $filters);
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
        return $this->goodsReceiptManager->getGoodsReceiptsForTenant($tenantId, $filters);
    }
}
