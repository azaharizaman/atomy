<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Contracts;

interface GoodsReceiptInterface
{
    public function getId(): string;

    public function getGrnNumber(): string;

    public function getLines(): array;
}
