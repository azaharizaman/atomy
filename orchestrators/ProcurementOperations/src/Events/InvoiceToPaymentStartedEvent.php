<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

/**
 * Event dispatched when invoice-to-payment workflow starts.
 */
final readonly class InvoiceToPaymentStartedEvent
{
    public function __construct(
        public string $tenantId,
        public ?string $invoiceId,
        public ?string $vendorId,
        public int $amountCents,
        public string $initiatedBy,
        public \DateTimeImmutable $startedAt = new \DateTimeImmutable(),
    ) {}
}
