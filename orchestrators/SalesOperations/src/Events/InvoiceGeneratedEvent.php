<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Events;

final readonly class InvoiceGeneratedEvent
{
    public function __construct(
        public string $tenantId,
        public string $invoiceId,
        public string $invoiceNumber,
        public string $orderId,
        public string $customerId,
        public float $totalAmount,
        public float $balanceDue,
        public string $currencyCode,
        public \DateTimeImmutable $dueDate,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
}
