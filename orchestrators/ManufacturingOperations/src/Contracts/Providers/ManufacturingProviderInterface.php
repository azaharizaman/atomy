<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\Contracts\Providers;

use Nexus\ManufacturingOperations\DTOs\ProductionOrder;
use Nexus\ManufacturingOperations\DTOs\ProductionOrderRequest;
use Nexus\ManufacturingOperations\DTOs\ProductionOrderStatus;

interface ManufacturingProviderInterface
{
    /**
     * Create a new production order.
     */
    public function createOrder(string $tenantId, ProductionOrderRequest $request): ProductionOrder;

    /**
     * Update the status of a production order.
     */
    public function updateOrderStatus(string $tenantId, string $orderId, ProductionOrderStatus $status): void;

    /**
     * Get details of a production order.
     * 
     * @throws \Nexus\ManufacturingOperations\Exceptions\OrderNotFoundException
     */
    public function getOrder(string $tenantId, string $orderId): ProductionOrder;

    /**
     * Record progress on a production order operation.
     */
    public function recordProgress(string $tenantId, string $orderId, string $operationId, float $quantity, float $hours): void;
}
