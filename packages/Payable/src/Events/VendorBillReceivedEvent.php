<?php

declare(strict_types=1);

namespace Nexus\Payable\Events;

/**
 * Dispatched when a vendor bill/invoice is received and recorded.
 *
 * Consuming applications or orchestrators may listen to this event
 * to trigger side-effects such as:
 * - Initiate 3-way matching process
 * - Notify AP team
 * - Audit logging
 */
final readonly class VendorBillReceivedEvent
{
    /**
     * @param string $vendorBillId Unique identifier of the vendor bill
     * @param string $tenantId Tenant context
     * @param string $vendorBillNumber System-generated bill number
     * @param string $vendorInvoiceNumber Vendor's invoice number
     * @param string $vendorId Vendor party ID
     * @param string|null $purchaseOrderId Associated PO (if applicable)
     * @param string|null $purchaseOrderNumber Associated PO number
     * @param array<int, array{
     *     lineId: string,
     *     poLineId: string|null,
     *     productId: string|null,
     *     description: string,
     *     quantity: float,
     *     unitPriceCents: int,
     *     lineTotalCents: int,
     *     taxCode: string|null,
     *     taxAmountCents: int
     * }> $lineItems Invoice line items
     * @param int $subtotalCents Subtotal in cents (before tax)
     * @param int $taxAmountCents Total tax amount in cents
     * @param int $totalAmountCents Total invoice amount in cents (including tax)
     * @param string $currency Currency code (ISO 4217)
     * @param \DateTimeImmutable $invoiceDate Invoice date from vendor
     * @param \DateTimeImmutable $dueDate Payment due date
     * @param string $paymentTerms Payment terms code
     * @param string $receivedBy User ID who recorded the bill
     * @param \DateTimeImmutable $receivedAt Timestamp when recorded
     */
    public function __construct(
        private string $vendorBillId,
        private string $tenantId,
        private string $vendorBillNumber,
        private string $vendorInvoiceNumber,
        private string $vendorId,
        private ?string $purchaseOrderId,
        private ?string $purchaseOrderNumber,
        private array $lineItems,
        private int $subtotalCents,
        private int $taxAmountCents,
        private int $totalAmountCents,
        private string $currency,
        private \DateTimeImmutable $invoiceDate,
        private \DateTimeImmutable $dueDate,
        private string $paymentTerms,
        private string $receivedBy,
        private \DateTimeImmutable $receivedAt,
    ) {}

    public function getVendorBillId(): string
    {
        return $this->vendorBillId;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getVendorBillNumber(): string
    {
        return $this->vendorBillNumber;
    }

    public function getVendorInvoiceNumber(): string
    {
        return $this->vendorInvoiceNumber;
    }

    public function getVendorId(): string
    {
        return $this->vendorId;
    }

    public function getPurchaseOrderId(): ?string
    {
        return $this->purchaseOrderId;
    }

    public function getPurchaseOrderNumber(): ?string
    {
        return $this->purchaseOrderNumber;
    }

    /**
     * @return array<int, array{
     *     lineId: string,
     *     poLineId: string|null,
     *     productId: string|null,
     *     description: string,
     *     quantity: float,
     *     unitPriceCents: int,
     *     lineTotalCents: int,
     *     taxCode: string|null,
     *     taxAmountCents: int
     * }>
     */
    public function getLineItems(): array
    {
        return $this->lineItems;
    }

    public function getSubtotalCents(): int
    {
        return $this->subtotalCents;
    }

    public function getTaxAmountCents(): int
    {
        return $this->taxAmountCents;
    }

    public function getTotalAmountCents(): int
    {
        return $this->totalAmountCents;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getInvoiceDate(): \DateTimeImmutable
    {
        return $this->invoiceDate;
    }

    public function getDueDate(): \DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function getPaymentTerms(): string
    {
        return $this->paymentTerms;
    }

    public function getReceivedBy(): string
    {
        return $this->receivedBy;
    }

    public function getReceivedAt(): \DateTimeImmutable
    {
        return $this->receivedAt;
    }
}
