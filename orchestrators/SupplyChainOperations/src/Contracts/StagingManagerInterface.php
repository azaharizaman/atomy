<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Contracts;

interface StagingManagerInterface
{
    public function moveToStaging(
        string $tenantId,
        string $warehouseId,
        string $productId,
        float $quantity,
        string $orderId,
        ?string $reference = null
    ): bool;

    public function getStagingQuantity(string $tenantId, string $warehouseId, string $orderId): float;
}
