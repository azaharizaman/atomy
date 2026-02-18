<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Contracts;

interface PurchaseOrderLineInterface
{
    public function getProductVariantId(): string;

    public function getUnitPrice(): float;
}
