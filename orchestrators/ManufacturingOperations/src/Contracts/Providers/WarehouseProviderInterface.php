<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\Contracts\Providers;

use Nexus\ManufacturingOperations\DTOs\ProductionOrder;

interface WarehouseProviderInterface
{
    /**
     * Resolve the target warehouse for finished goods receipt.
     */
    public function resolveWarehouse(string $tenantId, ProductionOrder $order): string;

    /**
     * Get the default warehouse for a tenant.
     */
    public function getDefaultWarehouse(string $tenantId): string;
}
