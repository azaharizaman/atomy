<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Request DTO for recording goods receipt.
 */
final readonly class RecordGoodsReceiptRequest
{
    /**
     * @param string $tenantId Tenant context
     * @param string $purchaseOrderId Purchase order being received
     * @param string $warehouseId Receiving warehouse
     * @param string $receivedBy User ID recording the receipt
     * @param array<int, array{
     *     poLineId: string,
     *     productId: string,
     *     quantityReceived: float,
     *     uom: string,
     *     lotNumber?: string|null,
     *     serialNumbers?: array<string>|null,
     *     expiryDate?: string|null,
     *     binLocation?: string|null,
     *     qualityStatus?: string,
     *     notes?: string|null
     * }> $lineItems Receipt line items
     * @param \DateTimeImmutable|null $receiptDate Receipt date (default: now)
     * @param string|null $deliveryNoteNumber Vendor's delivery note number
     * @param string|null $carrierName Carrier/transporter name
     * @param string|null $notes Receipt notes
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $tenantId,
        public string $purchaseOrderId,
        public string $warehouseId,
        public string $receivedBy,
        public array $lineItems,
        public ?\DateTimeImmutable $receiptDate = null,
        public ?string $deliveryNoteNumber = null,
        public ?string $carrierName = null,
        public ?string $notes = null,
        public array $metadata = [],
    ) {}
}
