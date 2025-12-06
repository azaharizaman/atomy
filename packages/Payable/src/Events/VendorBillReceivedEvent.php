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
        public readonly string $vendorBillId,
        public readonly string $tenantId,
        public readonly string $vendorBillNumber,
        public readonly string $vendorInvoiceNumber,
        public readonly string $vendorId,
        public readonly ?string $purchaseOrderId,
        public readonly ?string $purchaseOrderNumber,
        public readonly array $lineItems,
        public readonly int $subtotalCents,
        public readonly int $taxAmountCents,
        public readonly int $totalAmountCents,
        public readonly string $currency,
        public readonly \DateTimeImmutable $invoiceDate,
        public readonly \DateTimeImmutable $dueDate,
        public readonly string $paymentTerms,
        public readonly string $receivedBy,
        public readonly \DateTimeImmutable $receivedAt,
    ) {}
}
