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
        public readonly string $paymentScheduleId,
        public readonly string $tenantId,
        public readonly string $paymentBatchId,
        public readonly string $vendorBillId,
        public readonly string $vendorBillNumber,
        public readonly string $vendorId,
        public readonly string $vendorName,
        public readonly int $scheduledAmountCents,
        public readonly string $currency,
        public readonly \DateTimeImmutable $scheduledPaymentDate,
        public readonly string $paymentMethod,
        public readonly ?string $bankAccountId,
        public readonly string $scheduledBy,
        public readonly \DateTimeImmutable $scheduledAt,
    ) {}
}
