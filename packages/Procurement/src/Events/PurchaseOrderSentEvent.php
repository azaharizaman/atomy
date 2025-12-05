<?php

declare(strict_types=1);

namespace Nexus\Procurement\Events;

/**
 * Dispatched when a purchase order is sent to the vendor.
 *
 * Consuming applications or orchestrators may listen to this event
 * to trigger side-effects such as:
 * - Vendor notification (email, EDI, etc.)
 * - Status tracking
 * - Audit logging
 */
final readonly class PurchaseOrderSentEvent
{
    /**
     * @param string $purchaseOrderId Unique identifier of the PO
     * @param string $tenantId Tenant context
     * @param string $purchaseOrderNumber Human-readable PO number
     * @param string $vendorId Vendor party ID
     * @param string $sentBy User ID who sent the PO
     * @param string $deliveryMethod Method used to send (email, edi, portal, fax)
     * @param string|null $deliveryAddress Email address, EDI ID, or portal URL
     * @param \DateTimeImmutable $sentAt Timestamp when sent
     */
    public function __construct(
        public string $purchaseOrderId,
        public string $tenantId,
        public string $purchaseOrderNumber,
        public string $vendorId,
        public string $sentBy,
        public string $deliveryMethod,
        public ?string $deliveryAddress,
        public \DateTimeImmutable $sentAt,
    ) {}
}
