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
    public function submitRequisitionForApproval(string $tenantId, string $requisitionId): RequisitionInterface
    {
        $this->logger->info('ProcurementManager: Submitting requisition for approval', [
            'tenant_id' => $tenantId,
            'requisition_id' => $requisitionId,
        ]);

        return $this->requisitionManager->submitForApproval($tenantId, $requisitionId);
    }

    /**
     * {@inheritdoc}
     */
    public function approveRequisition(string $tenantId, string $requisitionId, string $approverId): RequisitionInterface
    {
        $this->logger->info('ProcurementManager: Approving requisition', [
            'tenant_id' => $tenantId,
            'requisition_id' => $requisitionId,
            'approver_id' => $approverId,
        ]);

        return $this->requisitionManager->approveRequisition($tenantId, $requisitionId, $approverId);
    }

    /**
     * {@inheritdoc}
     */
    public function rejectRequisition(string $tenantId, string $requisitionId, string $rejectorId, string $reason): RequisitionInterface
    {
        $this->logger->info('ProcurementManager: Rejecting requisition', [
            'tenant_id' => $tenantId,
            'requisition_id' => $requisitionId,
            'rejector_id' => $rejectorId,
            'reason' => $reason,
        ]);

        return $this->requisitionManager->rejectRequisition($tenantId, $requisitionId, $rejectorId, $reason);
    }

    /**
     * {@inheritdoc}
     */
    public function convertRequisitionToPO(
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

        return $this->purchaseOrderManager->createBlanketPo($tenantId, $creatorId, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function releasePO(string $tenantId, string $poId, string $releasedBy): PurchaseOrderInterface
    {
        $this->logger->info('ProcurementManager: Releasing purchase order', [
            'tenant_id' => $tenantId,
            'po_id' => $poId,
            'released_by' => $releasedBy,
        ]);

        return $this->purchaseOrderManager->releasePo($tenantId, $poId, $releasedBy);
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
    public function getRequisition(string $tenantId, string $requisitionId): RequisitionInterface
    {
        return $this->requisitionManager->getRequisition($tenantId, $requisitionId);
    }

    /**
     * {@inheritdoc}
     */
    public function getPurchaseOrder(string $tenantId, string $poId): PurchaseOrderInterface
    {
        return $this->purchaseOrderManager->getPurchaseOrder($tenantId, $poId);
    }

    /**
     * {@inheritdoc}
     */
    public function getGoodsReceipt(string $tenantId, string $grnId): GoodsReceiptNoteInterface
    {
        return $this->goodsReceiptManager->getGoodsReceipt($tenantId, $grnId);
    }

    /**
     * Create vendor quote for requisition.
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
     * @param string $tenantId
     * @param string $requisitionId
     * @return array Quote comparison matrix
     */
    public function compareVendorQuotes(string $tenantId, string $requisitionId): array
    {
        $this->logger->info('ProcurementManager: Comparing vendor quotes', [
            'tenant_id' => $tenantId,
            'requisition_id' => $requisitionId,
        ]);

        return $this->vendorQuoteManager->compareQuotes($tenantId, $requisitionId);
    }

    /**
     * Accept vendor quote.
     *
     * @param string $tenantId
     * @param string $quoteId
     * @param string $acceptorId
     * @return VendorQuoteInterface
     */
    public function acceptVendorQuote(string $tenantId, string $quoteId, string $acceptorId): VendorQuoteInterface
    {
        $this->logger->info('ProcurementManager: Accepting vendor quote', [
            'tenant_id' => $tenantId,
            'quote_id' => $quoteId,
            'acceptor_id' => $acceptorId,
        ]);

        return $this->vendorQuoteManager->acceptQuote($tenantId, $quoteId, $acceptorId);
    }

    /**
     * Lock a vendor quote for an active comparison run.
     */
    public function lockVendorQuote(string $tenantId, string $quoteId, string $comparisonRunId, string $lockedBy): VendorQuoteInterface
    {
        $this->logger->info('ProcurementManager: Locking vendor quote', [
            'tenant_id' => $tenantId,
            'quote_id' => $quoteId,
            'comparison_run_id' => $comparisonRunId,
            'locked_by' => $lockedBy,
        ]);

        return $this->vendorQuoteManager->lockQuote($tenantId, $quoteId, $comparisonRunId, $lockedBy);
    }

    /**
     * Unlock a vendor quote when a comparison run completes or is discarded.
     */
    public function unlockVendorQuote(string $tenantId, string $quoteId, string $comparisonRunId): VendorQuoteInterface
    {
        $this->logger->info('ProcurementManager: Unlocking vendor quote', [
            'tenant_id' => $tenantId,
            'quote_id' => $quoteId,
            'comparison_run_id' => $comparisonRunId,
        ]);

        return $this->vendorQuoteManager->unlockQuote($tenantId, $quoteId, $comparisonRunId);
    }

    /**
     * Release all locks held by a specific comparison run.
     */
    public function unlockAllVendorQuotesForRun(string $tenantId, string $comparisonRunId): int
    {
        $this->logger->info('ProcurementManager: Batch unlocking vendor quotes for run', [
            'tenant_id' => $tenantId,
            'comparison_run_id' => $comparisonRunId,
        ]);

        return $this->vendorQuoteManager->unlockAllForRun($tenantId, $comparisonRunId);
    }

    /**
     * Authorize payment for goods receipt.
     *
     * @param string $tenantId
     * @param string $grnId
     * @param string $authorizerId
     * @return GoodsReceiptNoteInterface
     */
    public function authorizeGrnPayment(string $tenantId, string $grnId, string $authorizerId): GoodsReceiptNoteInterface
    {
        $this->logger->info('ProcurementManager: Authorizing GRN payment', [
            'tenant_id' => $tenantId,
            'grn_id' => $grnId,
            'authorizer_id' => $authorizerId,
        ]);

        return $this->goodsReceiptManager->authorizePayment($tenantId, $grnId, $authorizerId);
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
