<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Contracts;

interface StockLevelProviderInterface
{
    public function find(string $tenantId, string $warehouseId, string $productId): ?StockLevelInterface;

    public function findByWarehouse(string $tenantId, string $warehouseId): array;
}
