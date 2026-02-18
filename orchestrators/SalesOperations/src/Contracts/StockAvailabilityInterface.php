<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Contracts;

interface StockAvailabilityInterface
{
    public function getAvailableQuantity(string $tenantId, string $productId, string $warehouseId): float;

    public function checkAvailability(string $tenantId, string $productId, string $warehouseId, float $quantity): AvailabilityResultInterface;

    public function getTotalAvailableQuantity(string $tenantId, string $productId): float;
}
