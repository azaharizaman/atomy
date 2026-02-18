<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Events;

final readonly class OrderConfirmedEvent
{
    public function __construct(
        public string $tenantId,
        public string $orderId,
        public string $orderNumber,
        public string $customerId,
        public float $totalAmount,
        public string $currencyCode,
        public array $lines = [],
        public ?string $warehouseId = null,
        public ?string $confirmedBy = null,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
}
