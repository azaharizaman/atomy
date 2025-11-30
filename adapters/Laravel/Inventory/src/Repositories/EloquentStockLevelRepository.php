<?php

declare(strict_types=1);

namespace Nexus\Laravel\Inventory\Repositories;

use Nexus\Inventory\Contracts\StockLevelRepositoryInterface;
use Nexus\Laravel\Inventory\Models\StockLevel;

/**
 * Eloquent implementation of Stock Level Repository
 */
final readonly class EloquentStockLevelRepository implements StockLevelRepositoryInterface
{
    public function getCurrentLevel(string $productId, string $warehouseId): float
    {
        $stockLevel = StockLevel::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if ($stockLevel === null) {
            return 0.0;
        }

        return (float) $stockLevel->quantity;
    }

    public function updateLevel(string $productId, string $warehouseId, float $quantity): void
    {
        StockLevel::updateOrCreate(
            [
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
            ],
            [
                'quantity' => $quantity,
            ]
        );
    }

    public function getReservedQuantity(string $productId, string $warehouseId): float
    {
        $stockLevel = StockLevel::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if ($stockLevel === null) {
            return 0.0;
        }

        return (float) $stockLevel->reserved_quantity;
    }
}
