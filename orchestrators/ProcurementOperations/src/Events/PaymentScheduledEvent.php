<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

/**
 * Event dispatched when a payment is scheduled.
 */
final readonly class PaymentScheduledEvent
{
    /**
     * @param string $tenantId Tenant context
     * @param string $paymentBatchId Payment batch ID
     * @param string|null $paymentId Payment ID
     * @param array<string> $vendorBillIds Vendor bills in payment
     * @param int $totalAmountCents Total payment amount in cents
     * @param string $currency Currency code
     * @param \DateTimeImmutable $scheduledDate Scheduled payment date
     * @param string $scheduledBy User who scheduled the payment
     */
    public function __construct(
        public string $tenantId,
        public string $paymentBatchId,
        public ?string $paymentId,
        public array $vendorBillIds,
        public int $totalAmountCents,
        public string $currency,
        public \DateTimeImmutable $scheduledDate,
        public string $scheduledBy,
    ) {}
}
