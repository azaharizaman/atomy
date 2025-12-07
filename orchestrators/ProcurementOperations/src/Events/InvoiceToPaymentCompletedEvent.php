<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

/**
 * Event dispatched when invoice-to-payment workflow completes successfully.
 */
final readonly class InvoiceToPaymentCompletedEvent
{
    public function __construct(
        public string $tenantId,
        public ?string $invoiceId,
        public ?string $paymentId,
        public int $paidAmountCents,
        public \DateTimeImmutable $completedAt,
    ) {}
}
