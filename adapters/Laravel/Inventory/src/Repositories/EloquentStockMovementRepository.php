<?php

declare(strict_types=1);

namespace Nexus\Laravel\Inventory\Repositories;

use Nexus\Inventory\Contracts\StockMovementRepositoryInterface;
use Nexus\Inventory\Enums\MovementType;
use Nexus\Laravel\Inventory\Models\StockMovement;

/**
 * Eloquent implementation of Stock Movement Repository
 */
final readonly class EloquentStockMovementRepository implements StockMovementRepositoryInterface
{
    /**
     * @return string Movement ID
     */
    public function recordMovement(
        string $productId,
        string $warehouseId,
        MovementType $type,
        float $quantity,
        float $unitCost,
        ?string $referenceId = null
    ): string {
        $movement = StockMovement::create([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'movement_type' => $type->value,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => bcmul((string) $quantity, (string) $unitCost, 4),
            'reference_id' => $referenceId,
        ]);

        return $movement->id;
    }

    /**
     * @return array<array{
     *     id: string,
     *     product_id: string,
     *     warehouse_id: string,
     *     movement_type: string,
     *     quantity: float,
     *     unit_cost: float,
     *     total_cost: float,
     *     reference_id: ?string,
     *     created_at: string
     * }>
     */
    public function getHistory(string $productId, ?string $warehouseId = null, int $limit = 100): array
    {
        $query = StockMovement::where('product_id', $productId);

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (StockMovement $m) => [
                'id' => $m->id,
                'product_id' => $m->product_id,
                'warehouse_id' => $m->warehouse_id,
                'movement_type' => $m->movement_type,
                'quantity' => (float) $m->quantity,
                'unit_cost' => (float) $m->unit_cost,
                'total_cost' => (float) $m->total_cost,
                'reference_id' => $m->reference_id,
                'created_at' => $m->created_at->toIso8601String(),
            ])
            ->all();
    }
}
