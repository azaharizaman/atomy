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
        private string $vendorBillId,
        private string $tenantId,
        private string $vendorBillNumber,
        private string $vendorId,
        private string $vendorName,
        private int $approvedAmountCents,
        private string $currency,
        private \DateTimeImmutable $dueDate,
        private string $paymentTerms,
        private ?int $earlyPaymentDiscountCents,
        private ?\DateTimeImmutable $discountDeadline,
        private string $approvedBy,
        private ?string $approvalComments,
        private \DateTimeImmutable $approvedAt,
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

    public function getVendorId(): string
    {
        return $this->vendorId;
    }

    public function getVendorName(): string
    {
        return $this->vendorName;
    }

    public function getApprovedAmountCents(): int
    {
        return $this->approvedAmountCents;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getDueDate(): \DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function getPaymentTerms(): string
    {
        return $this->paymentTerms;
    }

    public function getEarlyPaymentDiscountCents(): ?int
    {
        return $this->earlyPaymentDiscountCents;
    }

    public function getDiscountDeadline(): ?\DateTimeImmutable
    {
        return $this->discountDeadline;
    }

    public function getApprovedBy(): string
    {
        return $this->approvedBy;
    }

    public function getApprovalComments(): ?string
    {
        return $this->approvalComments;
    }

    public function getApprovedAt(): \DateTimeImmutable
    {
        return $this->approvedAt;
    }
}
