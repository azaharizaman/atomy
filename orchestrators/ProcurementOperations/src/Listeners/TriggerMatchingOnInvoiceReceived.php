<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Listeners;

use Nexus\Payable\Events\VendorBillReceivedEvent;
use Nexus\Procurement\Contracts\GoodsReceiptQueryInterface;
use Nexus\ProcurementOperations\Contracts\InvoiceMatchingCoordinatorInterface;
use Nexus\ProcurementOperations\DTOs\MatchInvoiceRequest;
use Nexus\ProcurementOperations\Exceptions\MatchingException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Triggers automatic three-way matching when a vendor invoice is received.
 *
 * This listener handles:
 * - Auto-matching for invoices with PO reference
 * - Finding matching goods receipts for the PO
 * - Initiating the three-way match process
 *
 * Prerequisites for auto-matching:
 * - Invoice must reference a purchase order
 * - At least one goods receipt must exist for the PO
 * - Quantities must be available for matching
 *
 * If auto-match fails due to variance, the invoice remains in
 * 'pending_match' status for manual review.
 */
final readonly class TriggerMatchingOnInvoiceReceived
{
    public function __construct(
        private InvoiceMatchingCoordinatorInterface $matchingCoordinator,
        private GoodsReceiptQueryInterface $goodsReceiptQuery,
        private bool $autoMatchEnabled = true,
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * Get the logger instance, or a NullLogger if none was injected.
     */
    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    /**
     * Handle the vendor bill received event.
     */
    public function handle(VendorBillReceivedEvent $event): void
    {
        $this->getLogger()->info('Vendor bill received, evaluating auto-match eligibility', [
            'vendor_bill_id' => $event->vendorBillId,
            'vendor_bill_number' => $event->vendorBillNumber,
            'purchase_order_id' => $event->purchaseOrderId,
            'vendor_id' => $event->vendorId,
            'total_amount_cents' => $event->totalAmountCents,
            'currency' => $event->currency,
        ]);

        // Skip if auto-matching is disabled
        if (!$this->autoMatchEnabled) {
            $this->getLogger()->info('Auto-matching is disabled, skipping', [
                'vendor_bill_id' => $event->vendorBillId,
            ]);
            return;
        }

        // Skip if invoice doesn't reference a PO (non-PO invoice)
        if ($event->purchaseOrderId === null) {
            $this->getLogger()->info('No PO reference on invoice, skipping auto-match', [
                'vendor_bill_id' => $event->vendorBillId,
                'vendor_bill_number' => $event->vendorBillNumber,
            ]);
            return;
        }

        try {
            // Find goods receipts for this PO
            $goodsReceiptIds = $this->findMatchableGoodsReceipts(
                tenantId: $event->tenantId,
                purchaseOrderId: $event->purchaseOrderId,
            );

            if (empty($goodsReceiptIds)) {
                $this->getLogger()->info('No goods receipts found for PO, cannot auto-match', [
                    'vendor_bill_id' => $event->vendorBillId,
                    'purchase_order_id' => $event->purchaseOrderId,
                ]);
                return;
            }

            $this->getLogger()->info('Found goods receipts for auto-matching', [
                'vendor_bill_id' => $event->vendorBillId,
                'purchase_order_id' => $event->purchaseOrderId,
                'goods_receipt_ids' => $goodsReceiptIds,
                'goods_receipt_count' => count($goodsReceiptIds),
            ]);

            // Build match request
            $request = new MatchInvoiceRequest(
                tenantId: $event->tenantId,
                vendorBillId: $event->vendorBillId,
                purchaseOrderId: $event->purchaseOrderId,
                goodsReceiptIds: $goodsReceiptIds,
                performedBy: 'system',
                allowVariance: false, // Auto-match does not allow variance
                varianceApprovalReason: null,
            );

            // Attempt three-way match
            $result = $this->matchingCoordinator->match($request);

            if ($result->matched) {
                $this->getLogger()->info('Auto-match successful', [
                    'vendor_bill_id' => $event->vendorBillId,
                    'purchase_order_id' => $event->purchaseOrderId,
                    'price_variance_percent' => $result->priceVariancePercent,
                    'quantity_variance_percent' => $result->quantityVariancePercent,
                    'journal_entry_id' => $result->journalEntryId,
                ]);
            } else {
                $this->getLogger()->warning('Auto-match failed due to variance', [
                    'vendor_bill_id' => $event->vendorBillId,
                    'purchase_order_id' => $event->purchaseOrderId,
                    'price_variance_percent' => $result->priceVariancePercent,
                    'quantity_variance_percent' => $result->quantityVariancePercent,
                    'failure_reason' => $result->failureReason,
                ]);
                // Invoice remains in 'pending_match' status for manual review
            }

        } catch (MatchingException $e) {
            $this->getLogger()->error('Auto-match failed with exception', [
                'vendor_bill_id' => $event->vendorBillId,
                'purchase_order_id' => $event->purchaseOrderId,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ]);
            // Invoice remains unmatched for manual intervention
        } catch (\Throwable $e) {
            $this->getLogger()->error('Unexpected error during auto-match', [
                'vendor_bill_id' => $event->vendorBillId,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);
            // Don't rethrow - allow invoice processing to continue
        }
    }

    /**
     * Find goods receipts that can be matched to this PO.
     *
     * @return array<string> Goods receipt IDs available for matching
     */
    private function findMatchableGoodsReceipts(
        string $tenantId,
        string $purchaseOrderId,
    ): array {
        // Query goods receipts for this PO that have unmatched quantities
        $goodsReceipts = $this->goodsReceiptQuery->findByPurchaseOrder(
            $tenantId,
            $purchaseOrderId,
        );

        $matchableIds = [];

        foreach ($goodsReceipts as $goodsReceipt) {
            // Only include GRs that are completed and have unmatched value
            if ($this->isMatchable($goodsReceipt)) {
                $matchableIds[] = $goodsReceipt->getId();
            }
        }

        return $matchableIds;
    }

    /**
     * Check if a goods receipt is eligible for invoice matching.
     *
     * @param object $goodsReceipt The goods receipt entity
     */
    private function isMatchable(object $goodsReceipt): bool
    {
        // GR must be completed
        if (method_exists($goodsReceipt, 'getStatus')) {
            $status = $goodsReceipt->getStatus();
            if ($status !== 'completed' && $status !== 'COMPLETED') {
                return false;
            }
        }

        // GR must have unmatched value (partially or fully unmatched)
        if (method_exists($goodsReceipt, 'getUnmatchedValue')) {
            $unmatchedValue = $goodsReceipt->getUnmatchedValue();
            if ($unmatchedValue <= 0) {
                return false;
            }
        }

        return true;
    }
}
