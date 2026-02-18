<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Contracts;

interface PurchaseOrderLineProviderInterface
{
    public function findLineByReference(string $reference): ?PurchaseOrderLineInterface;
}
