<?php

declare(strict_types=1);

namespace Nexus\Inventory\Contracts;

use Nexus\Inventory\Enums\MovementType;

/**
 * Stock movement repository interface
 */
interface StockMovementRepositoryInterface
{
    /**
     * Record stock movement
     * 
     * @return string Movement ID
     */
    public function recordMovement(
        string $productId,
        string $warehouseId,
        MovementType $type,
        float $quantity,
        float $unitCost,
        ?string $referenceId = null
    ): string;
    
    /**
     * Get movement history
     */
    public function getHistory(string $productId, ?string $warehouseId = null, int $limit = 100): array;
}
