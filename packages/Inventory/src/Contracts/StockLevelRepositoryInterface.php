<?php

declare(strict_types=1);

namespace Nexus\Inventory\Contracts;

/**
 * Stock level repository interface
 */
interface StockLevelRepositoryInterface
{
    /**
     * Get current stock level
     */
    public function getCurrentLevel(string $productId, string $warehouseId): float;
    
    /**
     * Update stock level
     */
    public function updateLevel(string $productId, string $warehouseId, float $quantity): void;
    
    /**
     * Get reserved quantity
     */
    public function getReservedQuantity(string $productId, string $warehouseId): float;

    /**
     * Get quarantine quantity
     */
    public function getQuarantineQuantity(string $productId, string $warehouseId): float;

    /**
     * Update quarantine quantity
     */
    public function updateQuarantineQuantity(string $productId, string $warehouseId, float $quantity): void;
}
