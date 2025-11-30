<?php

declare(strict_types=1);

namespace Nexus\Inventory\Events;

/**
 * Serial allocated event
 * 
 * Published when serial number is allocated to product instance
 */
final readonly class SerialAllocatedEvent
{
    public function __construct(
        public string $serialId,
        public string $serialNumber,
        public string $productId,
        public string $warehouseId,
        public \DateTimeImmutable $allocatedDate,
        public ?string $lotId = null
    ) {
    }
}
