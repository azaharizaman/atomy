<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Contracts;

interface StockReceivedEventInterface
{
    public function getProductId(): string;

    public function getWarehouseId(): string;

    public function getQuantity(): float;

    public function getGrnId(): ?string;

    public function getTenantId(): string;
}
