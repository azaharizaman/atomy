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
        public string $paymentId,
        public string $tenantId,
        public string $paymentReference,
        public string $vendorId,
        public string $vendorName,
        public array $affectedInvoiceIds,
        public int $attemptedAmountCents,
        public string $currency,
        public string $paymentMethod,
        public string $failureReason,
        public ?string $failureCode,
        public ?string $bankResponse,
        public int $attemptNumber,
        public bool $willRetry,
        public ?\DateTimeImmutable $nextRetryAt,
        public \DateTimeImmutable $failedAt,
    ) {}
}
