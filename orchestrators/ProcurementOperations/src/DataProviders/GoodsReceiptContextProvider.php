<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DataProviders;

use Nexus\Procurement\Contracts\GoodsReceiptQueryInterface;
use Nexus\Procurement\Contracts\PurchaseOrderQueryInterface;
use Nexus\Inventory\Contracts\StockQueryInterface;
use Nexus\Warehouse\Contracts\WarehouseQueryInterface;
use Nexus\Party\Contracts\VendorQueryInterface;
use Nexus\ProcurementOperations\DTOs\GoodsReceiptContext;
use Nexus\ProcurementOperations\Exceptions\GoodsReceiptException;
use Nexus\ProcurementOperations\Exceptions\PurchaseOrderException;

/**
 * Aggregates goods receipt data from multiple packages.
 *
 * Fetches GR information along with related data from:
 * - Procurement (PO details)
 * - Warehouse (warehouse info)
 * - Inventory (stock details)
 * - Party (vendor info)
 *
 * Following Advanced Orchestrator Pattern v1.1:
 * DataProviders abstract data fetching from Coordinators.
 */
final readonly class GoodsReceiptContextProvider
{
    public function __construct(
        private GoodsReceiptQueryInterface $goodsReceiptQuery,
        private PurchaseOrderQueryInterface $purchaseOrderQuery,
        private ?WarehouseQueryInterface $warehouseQuery = null,
        private ?StockQueryInterface $stockQuery = null,
        private ?VendorQueryInterface $vendorQuery = null,
    ) {}

    /**
     * Get full goods receipt context for workflow operations.
     *
     * @throws GoodsReceiptException If goods receipt not found
     */
    public function getContext(string $tenantId, string $goodsReceiptId): GoodsReceiptContext
    {
        $goodsReceipt = $this->goodsReceiptQuery->findById($goodsReceiptId);

        if ($goodsReceipt === null) {
            throw GoodsReceiptException::notFound($goodsReceiptId);
        }

        // Fetch associated purchase order
        $purchaseOrder = $this->purchaseOrderQuery->findById($goodsReceipt->getPurchaseOrderId());

        if ($purchaseOrder === null) {
            throw PurchaseOrderException::notFound($goodsReceipt->getPurchaseOrderId());
        }

        // Build line items array with extended information
        $lineItems = $this->buildLineItems($goodsReceipt, $purchaseOrder);

        // Fetch vendor info if Party package available
        $vendorInfo = $this->fetchVendorInfo($purchaseOrder->getVendorId());

        // Build PO summary info
        $purchaseOrderInfo = $this->buildPurchaseOrderInfo($purchaseOrder);

        // Fetch warehouse info if Warehouse package available
        $warehouseInfo = $this->fetchWarehouseInfo($goodsReceipt->getWarehouseId());

        return new GoodsReceiptContext(
            tenantId: $tenantId,
            goodsReceiptId: $goodsReceipt->getId(),
            goodsReceiptNumber: $goodsReceipt->getReceiptNumber(),
            status: $goodsReceipt->getStatus()->value,
            purchaseOrderId: $purchaseOrder->getId(),
            warehouseId: $goodsReceipt->getWarehouseId(),
            receivedBy: $goodsReceipt->getReceivedBy(),
            totalValueCents: $this->calculateTotalValue($lineItems),
            currency: $purchaseOrder->getCurrency(),
            lineItems: $lineItems,
            purchaseOrderInfo: $purchaseOrderInfo,
            warehouseInfo: $warehouseInfo,
            accrualJournalEntryId: $goodsReceipt->getAccrualJournalEntryId(),
            receiptDate: $goodsReceipt->getReceiptDate(),
            createdAt: $goodsReceipt->getCreatedAt(),
        );
    }

    /**
     * Get context for a new goods receipt (before persistence).
     *
     * @param string $tenantId
     * @param string $purchaseOrderId
     * @param string $warehouseId
     * @param array<int, array{
     *     poLineId: string,
     *     productId: string,
     *     quantityReceived: float,
     *     uom: string,
     *     lotNumber?: string|null,
     *     expiryDate?: \DateTimeImmutable|null,
     *     qualityStatus?: string
     * }> $lineItems
     *
     * @throws PurchaseOrderException If PO not found
     */
    public function getPreReceiptContext(
        string $tenantId,
        string $purchaseOrderId,
        string $warehouseId,
        array $lineItems
    ): GoodsReceiptContext {
        $purchaseOrder = $this->purchaseOrderQuery->findById($purchaseOrderId);

        if ($purchaseOrder === null) {
            throw PurchaseOrderException::notFound($purchaseOrderId);
        }

        // Build line items with PO price information
        $enrichedLineItems = [];
        $poLines = $this->indexPoLinesByLineId($purchaseOrder);

        foreach ($lineItems as $index => $line) {
            $poLine = $poLines[$line['poLineId']] ?? null;
            $unitPriceCents = $poLine ? $poLine['unitPriceCents'] : 0;

            $enrichedLineItems[$index] = [
                'lineId' => '', // Not yet assigned
                'poLineId' => $line['poLineId'],
                'productId' => $line['productId'],
                'quantityReceived' => $line['quantityReceived'],
                'unitPriceCents' => $unitPriceCents,
                'uom' => $line['uom'],
                'lotNumber' => $line['lotNumber'] ?? null,
                'serialNumbers' => null,
                'binLocation' => null,
                'qualityStatus' => $line['qualityStatus'] ?? 'pending',
                'expiryDate' => isset($line['expiryDate']) ? $line['expiryDate']->format('Y-m-d') : null,
                'poQuantity' => $poLine ? $poLine['quantity'] : 0.0,
                'poDescription' => $poLine ? $poLine['description'] : '',
            ];
        }

        // Fetch vendor and warehouse info
        $vendorInfo = $this->fetchVendorInfo($purchaseOrder->getVendorId());
        $warehouseInfo = $this->fetchWarehouseInfo($warehouseId);
        $purchaseOrderInfo = $this->buildPurchaseOrderInfo($purchaseOrder);

        return new GoodsReceiptContext(
            tenantId: $tenantId,
            goodsReceiptId: '', // Not yet created
            goodsReceiptNumber: '', // Not yet assigned
            status: 'pending',
            purchaseOrderId: $purchaseOrderId,
            warehouseId: $warehouseId,
            receivedBy: '',
            totalValueCents: $this->calculateTotalValue($enrichedLineItems),
            currency: $purchaseOrder->getCurrency(),
            lineItems: $enrichedLineItems,
            purchaseOrderInfo: $purchaseOrderInfo,
            warehouseInfo: $warehouseInfo,
            accrualJournalEntryId: null,
            receiptDate: null,
            createdAt: null,
        );
    }

    /**
     * Get outstanding quantities for a purchase order.
     *
     * @return array<string, array{
     *     lineId: string,
     *     productId: string,
     *     description: string,
     *     orderedQuantity: float,
     *     receivedQuantity: float,
     *     outstandingQuantity: float
     * }>
     *
     * @throws PurchaseOrderException If PO not found
     */
    public function getOutstandingQuantities(string $tenantId, string $purchaseOrderId): array
    {
        $purchaseOrder = $this->purchaseOrderQuery->findById($purchaseOrderId);

        if ($purchaseOrder === null) {
            throw PurchaseOrderException::notFound($purchaseOrderId);
        }

        // Get all receipts for this PO
        $receipts = $this->goodsReceiptQuery->findByPurchaseOrderId($purchaseOrderId);

        // Aggregate received quantities by PO line
        $receivedByLine = [];
        foreach ($receipts as $receipt) {
            foreach ($receipt->getLineItems() as $grLine) {
                $poLineId = $grLine->getPoLineId();
                $receivedByLine[$poLineId] = ($receivedByLine[$poLineId] ?? 0.0) + $grLine->getQuantityReceived();
            }
        }

        // Build result
        $result = [];
        foreach ($purchaseOrder->getLineItems() as $poLine) {
            $lineId = $poLine->getId();
            $received = $receivedByLine[$lineId] ?? 0.0;
            $ordered = $poLine->getQuantity();

            $result[$lineId] = [
                'lineId' => $lineId,
                'productId' => $poLine->getProductId(),
                'description' => $poLine->getDescription(),
                'orderedQuantity' => $ordered,
                'receivedQuantity' => $received,
                'outstandingQuantity' => max(0, $ordered - $received),
            ];
        }

        return $result;
    }

    /**
     * Build line items with extended information.
     */
    private function buildLineItems(object $goodsReceipt, object $purchaseOrder): array
    {
        $poLines = $this->indexPoLinesByLineId($purchaseOrder);
        $lineItems = [];

        foreach ($goodsReceipt->getLineItems() as $index => $grLine) {
            $poLine = $poLines[$grLine->getPoLineId()] ?? null;

            $lineItems[$index] = [
                'lineId' => $grLine->getId(),
                'poLineId' => $grLine->getPoLineId(),
                'productId' => $grLine->getProductId(),
                'quantityReceived' => $grLine->getQuantityReceived(),
                'unitPriceCents' => $poLine ? $poLine['unitPriceCents'] : 0,
                'uom' => $grLine->getUom(),
                'lotNumber' => $grLine->getLotNumber(),
                'serialNumbers' => $grLine->getSerialNumbers(),
                'binLocation' => $grLine->getBinLocation(),
                'qualityStatus' => $grLine->getQualityStatus()?->value ?? 'pending',
            ];
        }

        return $lineItems;
    }

    /**
     * Index PO lines by line ID for quick lookup.
     *
     * @return array<string, array{
     *     lineId: string,
     *     productId: string,
     *     description: string,
     *     quantity: float,
     *     unitPriceCents: int,
     *     uom: string
     * }>
     */
    private function indexPoLinesByLineId(object $purchaseOrder): array
    {
        $indexed = [];

        foreach ($purchaseOrder->getLineItems() as $poLine) {
            $indexed[$poLine->getId()] = [
                'lineId' => $poLine->getId(),
                'productId' => $poLine->getProductId(),
                'description' => $poLine->getDescription(),
                'quantity' => $poLine->getQuantity(),
                'unitPriceCents' => $poLine->getUnitPriceCents(),
                'uom' => $poLine->getUom(),
            ];
        }

        return $indexed;
    }

    /**
     * Build PO summary info.
     *
     * @return array{
     *     purchaseOrderId: string,
     *     purchaseOrderNumber: string,
     *     vendorId: string,
     *     vendorName: string,
     *     totalOrderedQuantity: float,
     *     totalReceivedQuantity: float
     * }
     */
    private function buildPurchaseOrderInfo(object $purchaseOrder): array
    {
        $totalOrdered = 0.0;
        foreach ($purchaseOrder->getLineItems() as $line) {
            $totalOrdered += $line->getQuantity();
        }

        // Get vendor name if available
        $vendorName = '';
        if ($this->vendorQuery !== null) {
            $vendor = $this->vendorQuery->findById($purchaseOrder->getVendorId());
            if ($vendor !== null) {
                $vendorName = $vendor->getName();
            }
        }

        // Calculate total received from all GRs
        $totalReceived = 0.0;
        $receipts = $this->goodsReceiptQuery->findByPurchaseOrderId($purchaseOrder->getId());
        foreach ($receipts as $receipt) {
            foreach ($receipt->getLineItems() as $line) {
                $totalReceived += $line->getQuantityReceived();
            }
        }

        return [
            'purchaseOrderId' => $purchaseOrder->getId(),
            'purchaseOrderNumber' => $purchaseOrder->getPoNumber(),
            'vendorId' => $purchaseOrder->getVendorId(),
            'vendorName' => $vendorName,
            'totalOrderedQuantity' => $totalOrdered,
            'totalReceivedQuantity' => $totalReceived,
        ];
    }

    /**
     * Fetch vendor info if Party package available.
     *
     * @return array{
     *     vendorId: string,
     *     vendorCode: string,
     *     vendorName: string,
     *     paymentTerms: ?string,
     *     currency: string,
     *     isActive: bool
     * }|null
     */
    private function fetchVendorInfo(string $vendorId): ?array
    {
        if ($this->vendorQuery === null) {
            return null;
        }

        $vendor = $this->vendorQuery->findById($vendorId);

        if ($vendor === null) {
            return null;
        }

        return [
            'vendorId' => $vendor->getId(),
            'vendorCode' => $vendor->getCode(),
            'vendorName' => $vendor->getName(),
            'paymentTerms' => $vendor->getDefaultPaymentTerms(),
            'currency' => $vendor->getDefaultCurrency(),
            'isActive' => $vendor->isActive(),
        ];
    }

    /**
     * Fetch warehouse info if Warehouse package available.
     *
     * @return array{
     *     warehouseId: string,
     *     warehouseCode: string,
     *     warehouseName: string
     * }|null
     */
    private function fetchWarehouseInfo(string $warehouseId): ?array
    {
        if ($this->warehouseQuery === null) {
            return null;
        }

        $warehouse = $this->warehouseQuery->findById($warehouseId);

        if ($warehouse === null) {
            return null;
        }

        return [
            'warehouseId' => $warehouse->getId(),
            'warehouseCode' => $warehouse->getCode(),
            'warehouseName' => $warehouse->getName(),
        ];
    }

    /**
     * Calculate total value from line items.
     */
    private function calculateTotalValue(array $lineItems): int
    {
        $total = 0;

        foreach ($lineItems as $line) {
            $total += (int) ($line['quantityReceived'] * $line['unitPriceCents']);
        }

        return $total;
    }
}
