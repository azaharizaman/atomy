<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Contracts;

interface GoodsReceiptProviderInterface
{
    public function findById(string $grnId): ?GoodsReceiptInterface;
}
