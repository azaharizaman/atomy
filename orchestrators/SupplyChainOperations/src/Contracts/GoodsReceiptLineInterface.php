<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Contracts;

interface GoodsReceiptLineInterface
{
    public function getQuantity(): float;

    public function getPoLineReference(): string;
}
