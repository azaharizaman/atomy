<?php

declare(strict_types=1);

namespace Nexus\Warehouse\Contracts;

/**
 * Warehouse repository contract
 */
interface WarehouseRepositoryInterface
{
    /**
     * Find warehouse by ID
     * 
     * @param string $id Warehouse unique identifier
     * @return WarehouseInterface|null
     */
    public function findById(string $id): ?WarehouseInterface;
    
    /**
     * Find warehouse by code within tenant
     * 
     * @param string $tenantId Tenant unique identifier
     * @param string $code Warehouse code
     * @return WarehouseInterface|null
     */
    public function findByCode(string $tenantId, string $code): ?WarehouseInterface;
    
    /**
     * Find all active warehouses for a tenant
     * 
     * @param string $tenantId Tenant unique identifier
     * @return array<WarehouseInterface>
     */
    public function findByTenant(string $tenantId): array;
    
    /**
     * Save warehouse entity
     * 
     * @param WarehouseInterface $warehouse Warehouse entity to persist
     * @return void
     */
    public function save(WarehouseInterface $warehouse): void;
    
    /**
     * Delete warehouse by ID
     * 
     * @param string $id Warehouse unique identifier to delete
     * @return void
     */
    public function delete(string $id): void;
}
