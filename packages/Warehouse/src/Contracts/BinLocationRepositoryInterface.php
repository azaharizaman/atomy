<?php

declare(strict_types=1);

namespace Nexus\Warehouse\Contracts;

/**
 * Bin location repository contract
 */
interface BinLocationRepositoryInterface
{
    /**
     * Find bin location by ID
     * 
     * @param string $id Bin location unique identifier
     * @return BinLocationInterface|null
     */
    public function findById(string $id): ?BinLocationInterface;
    
    /**
     * Find bin location by code within warehouse
     * 
     * @param string $warehouseId Warehouse unique identifier
     * @param string $code Bin location code
     * @return BinLocationInterface|null
     */
    public function findByCode(string $warehouseId, string $code): ?BinLocationInterface;
    
    /**
     * Find all active bin locations in warehouse
     * 
     * @param string $warehouseId Warehouse unique identifier
     * @return array<BinLocationInterface>
     */
    public function findByWarehouse(string $warehouseId): array;
    
    /**
     * Save bin location entity
     * 
     * @param BinLocationInterface $binLocation Bin location entity to persist
     * @return void
     */
    public function save(BinLocationInterface $binLocation): void;
    
    /**
     * Delete bin location by ID
     * 
     * @param string $id Bin location unique identifier to delete
     * @return void
     */
    public function delete(string $id): void;
}
