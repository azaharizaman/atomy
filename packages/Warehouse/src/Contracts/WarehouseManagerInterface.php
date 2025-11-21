<?php

declare(strict_types=1);

namespace Nexus\Warehouse\Contracts;

/**
 * Warehouse management interface
 */
interface WarehouseManagerInterface
{
    /**
     * Create warehouse
     * 
     * @param string $code Warehouse code
     * @param string $name Warehouse name
     * @param array $metadata Additional warehouse metadata
     * @return string Created warehouse ID
     */
    public function createWarehouse(string $code, string $name, array $metadata = []): string;
    
    /**
     * Get warehouse details
     * 
     * @param string $warehouseId Warehouse unique identifier
     * @return array Warehouse data
     * @throws \Nexus\Warehouse\Exceptions\WarehouseNotFoundException When warehouse is not found
     */
    public function getWarehouse(string $warehouseId): array;
    
    /**
     * List warehouses for tenant
     * 
     * @return array<array> List of warehouse data arrays
     */
    public function listWarehouses(): array;
}
