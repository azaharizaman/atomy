<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\Events;

use Nexus\ManufacturingOperations\DTOs\ProductionOrder;

final readonly class OrderReleased
{
    public function __construct(
        public string $tenantId,
        public ProductionOrder $order,
    ) {}
}
