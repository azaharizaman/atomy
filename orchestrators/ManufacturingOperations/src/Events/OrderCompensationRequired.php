<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\Events;

final readonly class OrderCompensationRequired
{
    public function __construct(
        public string $tenantId,
        public string $orderId,
        public string $context,
        public string $error,
    ) {}
}
