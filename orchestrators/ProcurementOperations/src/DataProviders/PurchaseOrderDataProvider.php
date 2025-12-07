<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DataProviders;

use Nexus\Procurement\Contracts\PurchaseOrderQueryInterface;
use Nexus\Procurement\Contracts\GoodsReceiptQueryInterface;
use Nexus\Party\Contracts\VendorQueryInterface;
use Nexus\Budget\Contracts\BudgetQueryInterface;
use Nexus\ProcurementOperations\DTOs\PurchaseOrderContext;
use Nexus\ProcurementOperations\Exceptions\PurchaseOrderException;

/**
 * Aggregates purchase order data from multiple packages.
 *
 * Fetches PO information along with related data from
 * Party (vendor), Budget (commitment), and Procurement (goods receipts).
 */
final readonly class PurchaseOrderDataProvider
{
    public function __construct(
        private PurchaseOrderQueryInterface $purchaseOrderQuery,
        private ?GoodsReceiptQueryInterface $goodsReceiptQuery = null,
        private ?VendorQueryInterface $vendorQuery = null,
        private ?BudgetQueryInterface $budgetQuery = null,
    ) {}

    /**
     * Get full purchase order context for workflow operations.
     *
     * @throws PurchaseOrderException
     */
    public function getContext(string $tenantId, string $purchaseOrderId): PurchaseOrderContext
    {
        $purchaseOrder = $this->purchaseOrderQuery->findById($purchaseOrderId);

        if ($purchaseOrder === null) {
            throw PurchaseOrderException::notFound($purchaseOrderId);
        }

        // Calculate received quantities per line
        $receivedByLine = $this->calculateReceivedQuantities($purchaseOrderId);

        // Build line items array with receipt status
        $lineItems = [];
        foreach ($purchaseOrder->getLineItems() as $index => $line) {
            $received = $receivedByLine[$line->getId()] ?? 0.0;
            $lineItems[$index] = [
                'lineId' => $line->getId(),
                'productId' => $line->getProductId(),
                'description' => $line->getDescription(),
                'quantity' => $line->getQuantity(),
                'unitPriceCents' => $line->getUnitPriceCents(),
                'uom' => $line->getUom(),
                'taxCode' => $line->getTaxCode(),
                'deliveryDate' => $line->getDeliveryDate()?->format('Y-m-d'),
                'receivedQuantity' => $received,
                'outstandingQuantity' => max(0, $line->getQuantity() - $received),
            ];
        }

        // Fetch vendor info if Party package available
        $vendorInfo = null;
        if ($this->vendorQuery !== null) {
            $vendor = $this->vendorQuery->findById($purchaseOrder->getVendorId());
            if ($vendor !== null) {
                $vendorInfo = [
                    'vendorId' => $vendor->getId(),
                    'vendorCode' => $vendor->getCode(),
                    'vendorName' => $vendor->getName(),
                    'paymentTerms' => $vendor->getDefaultPaymentTerms(),
                    'currency' => $vendor->getDefaultCurrency(),
                    'isActive' => $vendor->isActive(),
                ];
            }
        }

        // Fetch budget commitment if Budget package available
        $budgetCommitment = null;
        if ($this->budgetQuery !== null) {
            $commitment = $this->budgetQuery->findCommitmentByReference(
                $purchaseOrder->getBudgetId() ?? '',
                'purchase_order',
                $purchaseOrderId
            );

            if ($commitment !== null) {
                $budgetCommitment = [
                    'budgetId' => $commitment->getBudgetId(),
                    'commitmentId' => $commitment->getId(),
                    'commitmentAmountCents' => $commitment->getAmountCents(),
                ];
            }
        }

        // Fetch goods receipt IDs
        $goodsReceiptIds = $this->getGoodsReceiptIds($purchaseOrderId);

        return new PurchaseOrderContext(
            tenantId: $tenantId,
            purchaseOrderId: $purchaseOrderId,
            purchaseOrderNumber: $purchaseOrder->getPurchaseOrderNumber(),
            status: $purchaseOrder->getStatus()->value,
            vendorId: $purchaseOrder->getVendorId(),
            requisitionId: $purchaseOrder->getRequisitionId(),
            totalAmountCents: $purchaseOrder->getTotalAmountCents(),
            currency: $purchaseOrder->getCurrency(),
            lineItems: $lineItems,
            vendorInfo: $vendorInfo,
            budgetCommitment: $budgetCommitment,
            goodsReceiptIds: $goodsReceiptIds,
            amendmentNumber: $purchaseOrder->getAmendmentNumber(),
            createdAt: $purchaseOrder->getCreatedAt(),
            sentAt: $purchaseOrder->getSentAt(),
        );
    }

    /**
     * Check if vendor is active.
     */
    public function isVendorActive(string $vendorId): bool
    {
        if ($this->vendorQuery === null) {
            return true; // Assume active if Party package not available
        }

        $vendor = $this->vendorQuery->findById($vendorId);

        return $vendor?->isActive() ?? false;
    }

    /**
     * Get outstanding quantities for a purchase order.
     *
     * @return array<string, array{
     *     lineId: string,
     *     productId: string,
     *     orderedQuantity: float,
     *     receivedQuantity: float,
     *     outstandingQuantity: float
     * }>
     */
    public function getOutstandingQuantities(string $purchaseOrderId): array
    {
        $purchaseOrder = $this->purchaseOrderQuery->findById($purchaseOrderId);

        if ($purchaseOrder === null) {
            return [];
        }

        $receivedByLine = $this->calculateReceivedQuantities($purchaseOrderId);
        $result = [];

        foreach ($purchaseOrder->getLineItems() as $line) {
            $received = $receivedByLine[$line->getId()] ?? 0.0;
            $result[$line->getId()] = [
                'lineId' => $line->getId(),
                'productId' => $line->getProductId(),
                'orderedQuantity' => $line->getQuantity(),
                'receivedQuantity' => $received,
                'outstandingQuantity' => max(0, $line->getQuantity() - $received),
            ];
        }

        return $result;
    }

    /**
     * Calculate received quantities per PO line.
     *
     * @return array<string, float>
     */
    private function calculateReceivedQuantities(string $purchaseOrderId): array
    {
        if ($this->goodsReceiptQuery === null) {
            return [];
        }

        $receipts = $this->goodsReceiptQuery->findByPurchaseOrderId($purchaseOrderId);
        $receivedByLine = [];

        foreach ($receipts as $receipt) {
            foreach ($receipt->getLineItems() as $line) {
                $poLineId = $line->getPurchaseOrderLineId();
                $receivedByLine[$poLineId] = ($receivedByLine[$poLineId] ?? 0.0) + $line->getQuantityReceived();
            }
        }

        return $receivedByLine;
    }

    /**
     * Get goods receipt IDs for a purchase order.
     *
     * @return array<string>
     */
    private function getGoodsReceiptIds(string $purchaseOrderId): array
    {
        if ($this->goodsReceiptQuery === null) {
            return [];
        }

        $receipts = $this->goodsReceiptQuery->findByPurchaseOrderId($purchaseOrderId);

        return array_map(fn($receipt) => $receipt->getId(), $receipts);
    }
}
