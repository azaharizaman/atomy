<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Events;

final readonly class QuotationCreatedEvent
{
    public function __construct(
        public string $tenantId,
        public string $quotationId,
        public string $quotationNumber,
        public string $customerId,
        public float $totalAmount,
        public string $currencyCode,
        public \DateTimeImmutable $validUntil,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
}
