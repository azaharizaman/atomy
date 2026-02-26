<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\Events;

use Nexus\ManufacturingOperations\DTOs\ProductionOrder;

abstract readonly class OrderLifecycleEvent
{
    public function __construct(
        public string $tenantId,
        public ProductionOrder $order,
    ) {}
}
