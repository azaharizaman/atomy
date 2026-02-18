<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Contracts;

interface StockLevelInterface
{
    public function getProductId(): string;

    public function getQuantity(): float;

    public function getReorderPoint(): float;

    public function getWarehouseId(): string;
}
