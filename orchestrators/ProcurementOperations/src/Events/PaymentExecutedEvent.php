<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

/**
 * Event dispatched when a payment is executed.
 */
final readonly class PaymentExecutedEvent
{
    /**
     * @param string $tenantId Tenant context
     * @param string $paymentBatchId Payment batch ID
     * @param string|null $paymentId Payment ID
     * @param array<string> $vendorBillIds Vendor bills paid
     * @param int $totalAmountCents Total payment amount in cents
     * @param string $currency Currency code
     * @param \DateTimeImmutable $executedAt Execution timestamp
     * @param string $executedBy User who executed the payment
     * @param string|null $bankReference Bank transaction reference
     * @param string|null $journalEntryId GL journal entry ID
     */
    public function __construct(
        public string $tenantId,
        public string $paymentBatchId,
        public ?string $paymentId,
        public array $vendorBillIds,
        public int $totalAmountCents,
        public string $currency,
        public \DateTimeImmutable $executedAt,
        public string $executedBy,
        public ?string $bankReference = null,
        public ?string $journalEntryId = null,
    ) {}
}
