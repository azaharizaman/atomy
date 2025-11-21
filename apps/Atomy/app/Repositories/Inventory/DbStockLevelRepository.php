<?php

declare(strict_types=1);

namespace App\Repositories\Inventory;

use App\Models\StockLevel;
use Illuminate\Support\Facades\DB;
use Nexus\Inventory\Contracts\StockLevelRepositoryInterface;

final class DbStockLevelRepository implements StockLevelRepositoryInterface
{
    public function getCurrentStock(string $productId, string $warehouseId): float
    {
        $level = StockLevel::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        return $level ? (float) $level->quantity_on_hand : 0.0;
    }

    public function getReservedStock(string $productId, string $warehouseId): float
    {
        $level = StockLevel::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        return $level ? (float) $level->quantity_reserved : 0.0;
    }

    public function getAvailableStock(string $productId, string $warehouseId): float
    {
        $level = StockLevel::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        return $level ? (float) $level->quantity_available : 0.0;
    }

    public function updateLevel(
        string $productId,
        string $warehouseId,
        float $quantityChange,
        ?float $reservedChange = null
    ): void {
        $level = StockLevel::firstOrNew([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
        ], [
            'tenant_id' => app('tenant.context')->getCurrentTenantId(),
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
        ]);

        $level->quantity_on_hand = bcadd((string) $level->quantity_on_hand, (string) $quantityChange, 4);

        if ($reservedChange !== null) {
            $level->quantity_reserved = bcadd((string) $level->quantity_reserved, (string) $reservedChange, 4);
        }

        $level->last_movement_at = now();
        $level->save();
    }

    public function setValuationMethod(string $productId, string $warehouseId, string $method): void
    {
        StockLevel::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->update(['valuation_method' => $method]);
    }

    public function updateAverageCost(string $productId, string $warehouseId, float $averageCost): void
    {
        StockLevel::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->update(['average_cost' => $averageCost]);
    }

    public function findByProduct(string $productId): array
    {
        return StockLevel::where('product_id', $productId)
            ->with('warehouse')
            ->get()
            ->map(fn($level) => [
                'warehouse_id' => $level->warehouse_id,
                'warehouse_code' => $level->warehouse->code,
                'quantity_on_hand' => (float) $level->quantity_on_hand,
                'quantity_reserved' => (float) $level->quantity_reserved,
                'quantity_available' => (float) $level->quantity_available,
                'average_cost' => (float) $level->average_cost,
            ])
            ->all();
    }
}
