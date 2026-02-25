<?php

declare(strict_types=1);

namespace Nexus\Orchestrators\ManufacturingOperations\Contracts;

use Nexus\Orchestrators\ManufacturingOperations\DTOs\ProductionOrderRequest;
use Nexus\Orchestrators\ManufacturingOperations\DTOs\ProductionOrder;

interface ManufacturingOrchestratorInterface
{
    /**
     * Orchestrates the creation of a production order.
     * Validates BOM, Routing, and creates the plan.
     */
    public function planProduction(string $tenantId, ProductionOrderRequest $request): ProductionOrder;

    /**
     * Releases a production order to the floor.
     * Reserves inventory, schedules resources.
     */
    public function releaseOrder(string $tenantId, string $orderId): ProductionOrder;

    /**
     * Completes a production order.
     * Final inspection, stock putaway, costing.
     */
    public function completeOrder(string $tenantId, string $orderId): ProductionOrder;
}
