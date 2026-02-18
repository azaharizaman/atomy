<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Contracts;

interface SalesOrderProviderInterface
{
    public function findByStatus(string $tenantId, string $status): array;

    public function findById(string $tenantId, string $orderId): ?SalesOrderInterface;
}
