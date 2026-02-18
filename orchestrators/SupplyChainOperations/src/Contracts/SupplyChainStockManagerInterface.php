<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Contracts;

interface SupplyChainStockManagerInterface
{
    public function getCurrentStock(string $productId, string $warehouseId): float;

    public function capitalizeLandedCost(string $productId, float $additionalCost): void;

    public function adjustStock(
        string $productId,
        string $warehouseId,
        float $adjustmentQty,
        string $reason
    ): void;

    public function receiveReturn(
        string $tenantId,
        string $productId,
        string $warehouseId,
        float $quantity,
        ?string $reference = null
    ): void;

    public function writeOff(
        string $tenantId,
        string $productId,
        string $warehouseId,
        float $quantity,
        string $reason,
        ?string $reference = null
    ): void;
}
