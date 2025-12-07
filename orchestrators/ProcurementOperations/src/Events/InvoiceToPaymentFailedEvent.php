<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

/**
 * Event dispatched when invoice-to-payment workflow fails.
 */
final readonly class InvoiceToPaymentFailedEvent
{
    public function __construct(
        public string $tenantId,
        public ?string $invoiceId,
        public ?string $failureReason,
        public ?string $failedStep,
        public \DateTimeImmutable $failedAt,
    ) {}
}
