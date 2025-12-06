<?php

declare(strict_types=1);

namespace Nexus\Payable\Events;

/**
 * Dispatched when a payment is scheduled in a payment batch.
 *
 * Consuming applications or orchestrators may listen to this event
 * to trigger side-effects such as:
 * - Update cash flow forecast
 * - Reserve bank balance
 * - Notify vendor of upcoming payment
 * - Audit logging
 */
final readonly class PaymentScheduledEvent
{
    /**
     * @param string $paymentScheduleId Unique identifier of the payment schedule
     * @param string $tenantId Tenant context
     * @param string $paymentBatchId Payment batch this schedule belongs to
     * @param string $vendorBillId Vendor bill being paid
     * @param string $vendorBillNumber Vendor bill number
     * @param string $vendorId Vendor party ID
     * @param string $vendorName Vendor display name
     * @param int $scheduledAmountCents Amount scheduled for payment in cents
     * @param string $currency Currency code (ISO 4217)
     * @param \DateTimeImmutable $scheduledPaymentDate Date payment is scheduled
     * @param string $paymentMethod Payment method (bank_transfer, cheque, etc.)
     * @param string|null $bankAccountId Bank account to pay from
     * @param string $scheduledBy User ID who scheduled the payment
     * @param \DateTimeImmutable $scheduledAt Timestamp when scheduled
     */
    public function __construct(
        private string $paymentScheduleId,
        private string $tenantId,
        private string $paymentBatchId,
        private string $vendorBillId,
        private string $vendorBillNumber,
        private string $vendorId,
        private string $vendorName,
        private int $scheduledAmountCents,
        private string $currency,
        private \DateTimeImmutable $scheduledPaymentDate,
        private string $paymentMethod,
        private ?string $bankAccountId,
        private string $scheduledBy,
        private \DateTimeImmutable $scheduledAt,
    ) {}

    public function getPaymentScheduleId(): string
    {
        return $this->paymentScheduleId;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getPaymentBatchId(): string
    {
        return $this->paymentBatchId;
    }

    public function getVendorBillId(): string
    {
        return $this->vendorBillId;
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

    public function getScheduledAmountCents(): int
    {
        return $this->scheduledAmountCents;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getScheduledPaymentDate(): \DateTimeImmutable
    {
        return $this->scheduledPaymentDate;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function getBankAccountId(): ?string
    {
        return $this->bankAccountId;
    }

    public function getScheduledBy(): string
    {
        return $this->scheduledBy;
    }

    public function getScheduledAt(): \DateTimeImmutable
    {
        return $this->scheduledAt;
    }
}
