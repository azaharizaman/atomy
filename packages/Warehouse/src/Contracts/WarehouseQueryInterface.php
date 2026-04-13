<?php

declare(strict_types=1);

namespace Nexus\Warehouse\Contracts;

use Nexus\Warehouse\Contracts\WarehouseInterface;

/**
 * Query interface for warehouse retrieval operations.
 */
interface WarehouseQueryInterface
{
    /**
     * Find warehouse by tenant and ID.
     *
     * @param string $tenantId Tenant ULID
     * @param string $id Warehouse ULID
     * @return WarehouseInterface|null
     */
    public function findByTenantAndId(string $tenantId, string $id): ?WarehouseInterface;

    /**
     * Find warehouse by code.
     *
     * @param string $tenantId Tenant ULID
     * @param string $code Warehouse code
     * @return WarehouseInterface|null
     */
    public function findByCode(string $tenantId, string $code): ?WarehouseInterface;

    /**
     * Find all warehouses for a tenant.
     *
     * @param string $tenantId Tenant ULID
     * @return array<WarehouseInterface>
     */
    public function findByTenant(string $tenantId): array;
}
