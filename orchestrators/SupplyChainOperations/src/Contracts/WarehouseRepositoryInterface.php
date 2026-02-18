<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Contracts;

interface WarehouseRepositoryInterface
{
    public function findByTenant(string $tenantId): array;
}
