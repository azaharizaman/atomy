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
        private string $purchaseOrderId,
        private string $tenantId,
        private string $purchaseOrderNumber,
        private string $vendorId,
        private ?string $requisitionId,
        private array $lineItems,
        private int $totalAmountCents,
        private string $currency,
        private string $paymentTerms,
        private ?string $contractId,
        private \DateTimeImmutable $createdAt,
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

    public function getRequisitionId(): ?string
    {
        return $this->requisitionId;
    }

    /**
     * @return array<int, array{
     *     lineId: string,
     *     productId: string,
     *     description: string,
     *     quantity: float,
     *     unitOfMeasure: string,
     *     unitPriceCents: int,
     *     currency: string,
     *     taxCode: string|null,
     *     expectedDeliveryDate: string|null
     * }>
     */
    public function getLineItems(): array
    {
        return $this->lineItems;
    }

    public function getTotalAmountCents(): int
    {
        return $this->totalAmountCents;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getPaymentTerms(): string
    {
        return $this->paymentTerms;
    }

    public function getContractId(): ?string
    {
        return $this->contractId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
