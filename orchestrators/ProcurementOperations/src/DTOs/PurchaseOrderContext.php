<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Context DTO for purchase order operations.
 *
 * Aggregates data from multiple packages needed for PO workflow.
 */
final readonly class PurchaseOrderContext
{
    /**
     * @param string $tenantId Tenant context
     * @param string $purchaseOrderId PO ID
     * @param string $purchaseOrderNumber PO number
     * @param string $status Current status
     * @param string $vendorId Vendor ID
     * @param string $requisitionId Source requisition ID
     * @param int $totalAmountCents Total amount in cents
     * @param string $currency Currency code
     * @param array<int, array{
     *     lineId: string,
     *     productId: string,
     *     description: string,
     *     quantity: float,
     *     unitPriceCents: int,
     *     uom: string,
     *     taxCode: ?string,
     *     deliveryDate: ?string,
     *     receivedQuantity: float,
     *     outstandingQuantity: float
     * }> $lineItems Line items with receipt status
     * @param array{
     *     vendorId: string,
     *     vendorCode: string,
     *     vendorName: string,
     *     paymentTerms: ?string,
     *     currency: string,
     *     isActive: bool
     * }|null $vendorInfo Vendor information
     * @param array{
     *     budgetId: string,
     *     commitmentId: string,
     *     commitmentAmountCents: int
     * }|null $budgetCommitment Budget commitment information
     * @param array<string>|null $goodsReceiptIds Associated goods receipt IDs
     * @param int $amendmentNumber Current amendment number
     * @param \DateTimeImmutable $createdAt Creation timestamp
     * @param \DateTimeImmutable|null $sentAt Sent to vendor timestamp
     */
    public function __construct(
        public string $tenantId,
        public string $purchaseOrderId,
        public string $purchaseOrderNumber,
        public string $status,
        public string $vendorId,
        public string $requisitionId,
        public int $totalAmountCents,
        public string $currency,
        public array $lineItems,
        public ?array $vendorInfo = null,
        public ?array $budgetCommitment = null,
        public ?array $goodsReceiptIds = null,
        public int $amendmentNumber = 0,
        public ?\DateTimeImmutable $createdAt = null,
        public ?\DateTimeImmutable $sentAt = null,
    ) {}

    /**
     * Check if PO is fully received.
     */
    public function isFullyReceived(): bool
    {
        foreach ($this->lineItems as $line) {
            if ($line['outstandingQuantity'] > 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get total outstanding quantity across all lines.
     */
    public function getTotalOutstandingQuantity(): float
    {
        return array_reduce(
            $this->lineItems,
            fn(float $carry, array $line) => $carry + $line['outstandingQuantity'],
            0.0
        );
    }
}
