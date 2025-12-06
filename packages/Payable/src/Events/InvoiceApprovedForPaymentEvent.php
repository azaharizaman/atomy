<?php

declare(strict_types=1);

namespace Nexus\Payable\Events;

/**
 * Dispatched when a vendor invoice is approved for payment.
 *
 * Consuming applications or orchestrators may listen to this event
 * to trigger side-effects such as:
 * - Schedule payment based on payment terms
 * - Update cash flow forecast
 * - Notify treasury
 * - Audit logging
 */
final readonly class InvoiceApprovedForPaymentEvent
{
    /**
     * @param string $vendorBillId Unique identifier of the vendor bill
     * @param string $tenantId Tenant context
     * @param string $vendorBillNumber System-generated bill number
     * @param string $vendorId Vendor party ID
     * @param string $vendorName Vendor display name
     * @param int $approvedAmountCents Approved payment amount in cents
     * @param string $currency Currency code (ISO 4217)
     * @param \DateTimeImmutable $dueDate Payment due date
     * @param string $paymentTerms Payment terms code
     * @param int|null $earlyPaymentDiscountCents Early payment discount amount (if applicable)
     * @param \DateTimeImmutable|null $discountDeadline Deadline for early payment discount
     * @param string $approvedBy User ID who approved
     * @param string|null $approvalComments Optional approval comments
     * @param \DateTimeImmutable $approvedAt Timestamp of approval
     */
    public function __construct(
        public readonly string $vendorBillId,
        public readonly string $tenantId,
        public readonly string $vendorBillNumber,
        public readonly string $vendorId,
        public readonly string $vendorName,
        public readonly int $approvedAmountCents,
        public readonly string $currency,
        public readonly \DateTimeImmutable $dueDate,
        public readonly string $paymentTerms,
        public readonly ?int $earlyPaymentDiscountCents,
        public readonly ?\DateTimeImmutable $discountDeadline,
        public readonly string $approvedBy,
        public readonly ?string $approvalComments,
        public readonly \DateTimeImmutable $approvedAt,
    ) {}
}
