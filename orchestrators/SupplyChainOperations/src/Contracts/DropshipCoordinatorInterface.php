<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Contracts;

interface DropshipCoordinatorInterface
{
    public function createDropshipPo(
        SalesOrderInterface $salesOrder,
        array $lines,
        string $vendorId
    ): string;
}
