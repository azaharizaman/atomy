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
        public string $vendorBillId,
        public string $tenantId,
        public string $vendorBillNumber,
        public string $vendorId,
        public string $vendorName,
        public int $approvedAmountCents,
        public string $currency,
        public \DateTimeImmutable $dueDate,
        public string $paymentTerms,
        public ?int $earlyPaymentDiscountCents,
        public ?\DateTimeImmutable $discountDeadline,
        public string $approvedBy,
        public ?string $approvalComments,
        public \DateTimeImmutable $approvedAt,
    ) {}
}
