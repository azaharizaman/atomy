<?php

declare(strict_types=1);

namespace Nexus\Payable\Events;

/**
 * Dispatched when a payment execution fails.
 *
 * Consuming applications or orchestrators may listen to this event
 * to trigger side-effects such as:
 * - Notify AP team
 * - Schedule retry
 * - Create exception task
 * - Update payment status
 * - Audit logging
 */
final readonly class PaymentFailedEvent
{
    /**
     * @param string $paymentId Unique identifier of the failed payment
     * @param string $tenantId Tenant context
     * @param string $paymentReference Payment reference number
     * @param string $vendorId Vendor party ID
     * @param string $vendorName Vendor display name
     * @param array<string> $affectedInvoiceIds Invoices that were to be paid
     * @param int $attemptedAmountCents Amount attempted in cents
     * @param string $currency Currency code (ISO 4217)
     * @param string $paymentMethod Payment method attempted
     * @param string $failureReason Human-readable failure reason
     * @param string|null $failureCode Error code from bank/payment processor
     * @param string|null $bankResponse Raw response from bank (if available)
     * @param int $attemptNumber Which attempt this was (for retry tracking)
     * @param bool $willRetry Whether system will auto-retry
     * @param \DateTimeImmutable|null $nextRetryAt Scheduled next retry time (if will retry)
     * @param \DateTimeImmutable $failedAt Timestamp of failure
     */
    public function __construct(
        private string $paymentId,
        private string $tenantId,
        private string $paymentReference,
        private string $vendorId,
        private string $vendorName,
        private array $affectedInvoiceIds,
        private int $attemptedAmountCents,
        private string $currency,
        private string $paymentMethod,
        private string $failureReason,
        private ?string $failureCode,
        private ?string $bankResponse,
        private int $attemptNumber,
        private bool $willRetry,
        private ?\DateTimeImmutable $nextRetryAt,
        private \DateTimeImmutable $failedAt,
    ) {}

    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getPaymentReference(): string
    {
        return $this->paymentReference;
    }

    public function getVendorId(): string
    {
        return $this->vendorId;
    }

    public function getVendorName(): string
    {
        return $this->vendorName;
    }

    /**
     * @return array<string>
     */
    public function getAffectedInvoiceIds(): array
    {
        return $this->affectedInvoiceIds;
    }

    public function getAttemptedAmountCents(): int
    {
        return $this->attemptedAmountCents;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function getFailureReason(): string
    {
        return $this->failureReason;
    }

    public function getFailureCode(): ?string
    {
        return $this->failureCode;
    }

    public function getBankResponse(): ?string
    {
        return $this->bankResponse;
    }

    public function getAttemptNumber(): int
    {
        return $this->attemptNumber;
    }

    public function willRetry(): bool
    {
        return $this->willRetry;
    }

    public function getNextRetryAt(): ?\DateTimeImmutable
    {
        return $this->nextRetryAt;
    }

    public function getFailedAt(): \DateTimeImmutable
    {
        return $this->failedAt;
    }
}
