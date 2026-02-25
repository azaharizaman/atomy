<?php

declare(strict_types=1);

namespace Nexus\Orchestrators\ManufacturingOperations\Contracts\Providers;

use Nexus\Orchestrators\ManufacturingOperations\DTOs\ProductionOrder;
use Nexus\Orchestrators\ManufacturingOperations\DTOs\ProductionOrderRequest;

interface ManufacturingProviderInterface
{
    /**
     * Create a new production order.
     */
    public function createOrder(string $tenantId, ProductionOrderRequest $request): ProductionOrder;

    /**
     * Update the status of a production order.
     */
    public function updateOrderStatus(string $tenantId, string $orderId, string $status): void;

    /**
     * Get details of a production order.
     * 
     * @throws \Nexus\Orchestrators\ManufacturingOperations\Exceptions\OrderNotFoundException
     */
    public function getOrder(string $tenantId, string $orderId): ProductionOrder;

    /**
     * Record progress on a production order operation.
     */
    public function recordProgress(string $tenantId, string $orderId, string $operationId, float $quantity, float $hours): void;
}
