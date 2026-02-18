<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Contracts;

interface StockReservationInterface
{
    public function reserve(string $tenantId, string $orderId, string $productId, string $warehouseId, float $quantity): bool;

    public function release(string $tenantId, string $orderId): void;

    public function releaseLine(string $tenantId, string $orderId, string $productId): void;

    public function getReservedQuantity(string $tenantId, string $orderId, string $productId): float;

    public function getReservationsByOrder(string $tenantId, string $orderId): array;

    public function convertToAllocated(string $tenantId, string $orderId): void;
}
