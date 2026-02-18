<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Events;

final readonly class OrderCancelledEvent
{
    public function __construct(
        public string $tenantId,
        public string $orderId,
        public string $orderNumber,
        public string $reason,
        public ?string $cancelledBy = null,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
}
