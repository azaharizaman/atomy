<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Events;

final readonly class QuotationAcceptedEvent
{
    public function __construct(
        public string $tenantId,
        public string $quotationId,
        public string $quotationNumber,
        public string $customerId,
        public ?string $orderId = null,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
}
