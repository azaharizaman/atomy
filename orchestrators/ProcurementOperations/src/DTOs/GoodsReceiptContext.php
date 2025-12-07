<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Context DTO for goods receipt operations.
 *
 * Aggregates data from multiple packages needed for GR workflow.
 */
final readonly class GoodsReceiptContext
{
    /**
     * @param string $tenantId Tenant context
     * @param string $goodsReceiptId GR ID
     * @param string $goodsReceiptNumber GR number
     * @param string $status Current status
     * @param string $purchaseOrderId Source PO ID
     * @param string $warehouseId Receiving warehouse
     * @param string $receivedBy User who recorded receipt
     * @param int $totalValueCents Total received value in cents (at PO prices)
     * @param string $currency Currency code
     * @param array<int, array{
     *     lineId: string,
     *     poLineId: string,
     *     productId: string,
     *     quantityReceived: float,
     *     unitPriceCents: int,
     *     uom: string,
     *     lotNumber: ?string,
     *     serialNumbers: ?array<string>,
     *     binLocation: ?string,
     *     qualityStatus: string
     * }> $lineItems Receipt line items
     * @param array{
     *     purchaseOrderId: string,
     *     purchaseOrderNumber: string,
     *     vendorId: string,
     *     vendorName: string,
     *     totalOrderedQuantity: float,
     *     totalReceivedQuantity: float
     * }|null $purchaseOrderInfo PO summary information
     * @param array{
     *     warehouseId: string,
     *     warehouseCode: string,
     *     warehouseName: string
     * }|null $warehouseInfo Warehouse information
     * @param string|null $accrualJournalEntryId GR-IR accrual journal entry ID
     * @param \DateTimeImmutable $receiptDate Receipt date
     * @param \DateTimeImmutable|null $createdAt Creation timestamp
     */
    public function __construct(
        public string $tenantId,
        public string $goodsReceiptId,
        public string $goodsReceiptNumber,
        public string $status,
        public string $purchaseOrderId,
        public string $warehouseId,
        public string $receivedBy,
        public int $totalValueCents,
        public string $currency,
        public array $lineItems,
        public ?array $purchaseOrderInfo = null,
        public ?array $warehouseInfo = null,
        public ?string $accrualJournalEntryId = null,
        public ?\DateTimeImmutable $receiptDate = null,
        public ?\DateTimeImmutable $createdAt = null,
    ) {}

    /**
     * Get total quantity received.
     */
    public function getTotalQuantityReceived(): float
    {
        return array_reduce(
            $this->lineItems,
            fn(float $carry, array $line) => $carry + $line['quantityReceived'],
            0.0
        );
    }
}
