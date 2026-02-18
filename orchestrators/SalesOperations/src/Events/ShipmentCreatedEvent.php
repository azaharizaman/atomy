<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Events;

final readonly class ShipmentCreatedEvent
{
    public function __construct(
        public string $tenantId,
        public string $shipmentId,
        public string $shipmentNumber,
        public string $orderId,
        public string $warehouseId,
        public array $lines = [],
        public ?string $trackingNumber = null,
        public ?string $shippedBy = null,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
}
