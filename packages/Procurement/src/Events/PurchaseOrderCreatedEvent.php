<?php

declare(strict_types=1);

namespace Nexus\Procurement\Events;

/**
 * Dispatched when a purchase order is created.
 *
 * Consuming applications or orchestrators may listen to this event
 * to trigger side-effects such as:
 * - Budget line-item tracking
 * - Vendor notification preparation
 * - Audit logging
 */
final readonly class PurchaseOrderCreatedEvent
{
    /**
     * @param string $purchaseOrderId Unique identifier of the PO
     * @param string $tenantId Tenant context
     * @param string $purchaseOrderNumber Human-readable PO number
     * @param string $vendorId Vendor party ID
     * @param string|null $requisitionId Source requisition (if converted from requisition)
     * @param array<int, array{
     *     lineId: string,
     *     productId: string,
     *     description: string,
     *     quantity: float,
     *     unitOfMeasure: string,
     *     unitPriceCents: int,
     *     currency: string,
     *     taxCode: string|null,
     *     expectedDeliveryDate: string|null
     * }> $lineItems PO line items
     * @param int $totalAmountCents Total PO amount in cents
     * @param string $currency Currency code (ISO 4217)
     * @param string $paymentTerms Payment terms code
     * @param string|null $contractId Associated contract (if blanket PO)
     * @param \DateTimeImmutable $createdAt Timestamp of creation
     */
    public function __construct(
        public readonly string $purchaseOrderId,
        public readonly string $tenantId,
        public readonly string $purchaseOrderNumber,
        public readonly string $vendorId,
        public readonly ?string $requisitionId,
        public readonly array $lineItems,
        public readonly int $totalAmountCents,
        public readonly string $currency,
        public readonly string $paymentTerms,
        public readonly ?string $contractId,
        public readonly \DateTimeImmutable $createdAt,
    ) {}
}
