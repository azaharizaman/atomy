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
        private string $purchaseOrderId,
        private string $tenantId,
        private string $purchaseOrderNumber,
        private string $vendorId,
        private string $sentBy,
        private string $deliveryMethod,
        private ?string $deliveryAddress,
        private \DateTimeImmutable $sentAt,
    ) {}

    public function getPurchaseOrderId(): string
    {
        return $this->purchaseOrderId;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getPurchaseOrderNumber(): string
    {
        return $this->purchaseOrderNumber;
    }

    public function getVendorId(): string
    {
        return $this->vendorId;
    }

    public function getSentBy(): string
    {
        return $this->sentBy;
    }

    public function getDeliveryMethod(): string
    {
        return $this->deliveryMethod;
    }

    public function getDeliveryAddress(): ?string
    {
        return $this->deliveryAddress;
    }

    public function getSentAt(): \DateTimeImmutable
    {
        return $this->sentAt;
    }
}
