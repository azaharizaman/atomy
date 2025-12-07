<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DataProviders;

use Nexus\Procurement\Contracts\PurchaseOrderQueryInterface;
use Nexus\Procurement\Contracts\GoodsReceiptQueryInterface;
use Nexus\Payable\Contracts\VendorBillQueryInterface;
use Nexus\ProcurementOperations\DTOs\ThreeWayMatchContext;
use Nexus\ProcurementOperations\Exceptions\MatchingException;
use Nexus\ProcurementOperations\Exceptions\PurchaseOrderException;

/**
 * Aggregates data for three-way matching operations.
 *
 * Fetches and correlates data from Purchase Orders, Goods Receipts,
 * and Vendor Bills for matching comparison.
 */
final readonly class ThreeWayMatchDataProvider
{
    public function __construct(
        private PurchaseOrderQueryInterface $purchaseOrderQuery,
        private GoodsReceiptQueryInterface $goodsReceiptQuery,
        private VendorBillQueryInterface $vendorBillQuery,
    ) {}

    /**
     * Build context for three-way matching.
     *
     * @param array<string> $goodsReceiptIds
     * @throws MatchingException
     * @throws PurchaseOrderException
     */
    public function buildContext(
        string $tenantId,
        string $vendorBillId,
        string $purchaseOrderId,
        array $goodsReceiptIds
    ): ThreeWayMatchContext {
        // Fetch invoice
        $vendorBill = $this->vendorBillQuery->findById($vendorBillId);
        if ($vendorBill === null) {
            throw MatchingException::invoiceNotFound($vendorBillId);
        }

        // Fetch purchase order
        $purchaseOrder = $this->purchaseOrderQuery->findById($purchaseOrderId);
        if ($purchaseOrder === null) {
            throw PurchaseOrderException::notFound($purchaseOrderId);
        }

        // Validate vendors match
        if ($vendorBill->getVendorId() !== $purchaseOrder->getVendorId()) {
            throw MatchingException::vendorMismatch(
                $vendorBillId,
                $vendorBill->getVendorId(),
                $purchaseOrderId,
                $purchaseOrder->getVendorId()
            );
        }

        // Validate currencies match
        if ($vendorBill->getCurrency() !== $purchaseOrder->getCurrency()) {
            throw MatchingException::currencyMismatch(
                $vendorBillId,
                $vendorBill->getCurrency(),
                $purchaseOrderId,
                $purchaseOrder->getCurrency()
            );
        }

        // Fetch and aggregate goods receipts
        $goodsReceipts = [];
        foreach ($goodsReceiptIds as $grId) {
            $gr = $this->goodsReceiptQuery->findById($grId);
            if ($gr !== null) {
                $goodsReceipts[] = $gr;
            }
        }

        // Build line comparison
        $lineComparison = $this->buildLineComparison($purchaseOrder, $goodsReceipts, $vendorBill);

        // Calculate totals
        $totals = $this->calculateTotals($purchaseOrder, $goodsReceipts, $vendorBill);

        return new ThreeWayMatchContext(
            tenantId: $tenantId,
            vendorBillId: $vendorBillId,
            purchaseOrderId: $purchaseOrderId,
            goodsReceiptIds: $goodsReceiptIds,
            invoiceInfo: [
                'billId' => $vendorBill->getId(),
                'billNumber' => $vendorBill->getBillNumber(),
                'vendorId' => $vendorBill->getVendorId(),
                'vendorName' => $vendorBill->getVendorName() ?? 'Unknown',
                'totalAmountCents' => $vendorBill->getTotalAmountCents(),
                'taxAmountCents' => $vendorBill->getTaxAmountCents(),
                'currency' => $vendorBill->getCurrency(),
                'invoiceDate' => $vendorBill->getInvoiceDate(),
                'dueDate' => $vendorBill->getDueDate(),
                'status' => $vendorBill->getStatus()->value,
            ],
            purchaseOrderInfo: [
                'poId' => $purchaseOrder->getId(),
                'poNumber' => $purchaseOrder->getPurchaseOrderNumber(),
                'vendorId' => $purchaseOrder->getVendorId(),
                'totalAmountCents' => $purchaseOrder->getTotalAmountCents(),
                'currency' => $purchaseOrder->getCurrency(),
                'status' => $purchaseOrder->getStatus()->value,
            ],
            lineComparison: $lineComparison,
            totals: $totals,
        );
    }

    /**
     * Build line-by-line comparison data.
     *
     * @param array<mixed> $goodsReceipts
     * @return array<int, array{
     *     lineId: string,
     *     productId: string,
     *     description: string,
     *     poQuantity: float,
     *     poUnitPriceCents: int,
     *     grQuantity: float,
     *     invoiceQuantity: float,
     *     invoiceUnitPriceCents: int,
     *     uom: string
     * }>
     */
    private function buildLineComparison(
        mixed $purchaseOrder,
        array $goodsReceipts,
        mixed $vendorBill
    ): array {
        $comparison = [];

        // Aggregate GR quantities by PO line
        $grQuantityByPoLine = [];
        foreach ($goodsReceipts as $gr) {
            foreach ($gr->getLineItems() as $grLine) {
                $poLineId = $grLine->getPurchaseOrderLineId();
                $grQuantityByPoLine[$poLineId] = ($grQuantityByPoLine[$poLineId] ?? 0.0) + $grLine->getQuantityReceived();
            }
        }

        // Map invoice lines to PO lines
        $invoiceByPoLine = [];
        foreach ($vendorBill->getLineItems() as $invoiceLine) {
            $poLineId = $invoiceLine->getPurchaseOrderLineId();
            if ($poLineId !== null) {
                $invoiceByPoLine[$poLineId] = [
                    'quantity' => $invoiceLine->getQuantity(),
                    'unitPriceCents' => $invoiceLine->getUnitPriceCents(),
                ];
            }
        }

        // Build comparison for each PO line
        foreach ($purchaseOrder->getLineItems() as $index => $poLine) {
            $invoiceData = $invoiceByPoLine[$poLine->getId()] ?? ['quantity' => 0.0, 'unitPriceCents' => 0];

            $comparison[$index] = [
                'lineId' => $poLine->getId(),
                'productId' => $poLine->getProductId(),
                'description' => $poLine->getDescription(),
                'poQuantity' => $poLine->getQuantity(),
                'poUnitPriceCents' => $poLine->getUnitPriceCents(),
                'grQuantity' => $grQuantityByPoLine[$poLine->getId()] ?? 0.0,
                'invoiceQuantity' => $invoiceData['quantity'],
                'invoiceUnitPriceCents' => $invoiceData['unitPriceCents'],
                'uom' => $poLine->getUom(),
            ];
        }

        return $comparison;
    }

    /**
     * Calculate totals for comparison.
     *
     * @param array<mixed> $goodsReceipts
     * @return array{
     *     totalPoAmountCents: int,
     *     totalGrValueCents: int,
     *     totalInvoiceAmountCents: int,
     *     totalPoQuantity: float,
     *     totalGrQuantity: float,
     *     totalInvoiceQuantity: float
     * }
     */
    private function calculateTotals(
        mixed $purchaseOrder,
        array $goodsReceipts,
        mixed $vendorBill
    ): array {
        $totalPoQuantity = 0.0;
        $totalPoAmountCents = 0;
        foreach ($purchaseOrder->getLineItems() as $line) {
            $totalPoQuantity += $line->getQuantity();
            $totalPoAmountCents += (int) ($line->getQuantity() * $line->getUnitPriceCents());
        }

        $totalGrQuantity = 0.0;
        $totalGrValueCents = 0;
        foreach ($goodsReceipts as $gr) {
            foreach ($gr->getLineItems() as $line) {
                $totalGrQuantity += $line->getQuantityReceived();
                $totalGrValueCents += (int) ($line->getQuantityReceived() * $line->getUnitPriceCents());
            }
        }

        $totalInvoiceQuantity = 0.0;
        foreach ($vendorBill->getLineItems() as $line) {
            $totalInvoiceQuantity += $line->getQuantity();
        }

        return [
            'totalPoAmountCents' => $totalPoAmountCents,
            'totalGrValueCents' => $totalGrValueCents,
            'totalInvoiceAmountCents' => $vendorBill->getTotalAmountCents(),
            'totalPoQuantity' => $totalPoQuantity,
            'totalGrQuantity' => $totalGrQuantity,
            'totalInvoiceQuantity' => $totalInvoiceQuantity,
        ];
    }
}
