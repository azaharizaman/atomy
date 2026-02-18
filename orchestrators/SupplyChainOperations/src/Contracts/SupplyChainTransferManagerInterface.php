<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Contracts;

interface SupplyChainTransferManagerInterface
{
    public function createTransfer(
        string $tenantId,
        string $productId,
        string $sourceWarehouseId,
        string $destinationWarehouseId,
        float $quantity,
        ?string $reason = null
    ): string;
}
