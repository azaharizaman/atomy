<?php

declare(strict_types=1);

namespace App\Repositories\Inventory;

use App\Models\StockMovement;
use Nexus\Inventory\Contracts\StockMovementRepositoryInterface;

final class DbStockMovementRepository implements StockMovementRepositoryInterface
{
    public function recordMovement(
        string $productId,
        string $warehouseId,
        string $movementType,
        float $quantity,
        ?float $unitCost = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $notes = null
    ): string {
        $movement = StockMovement::create([
            'tenant_id' => app('tenant.context')->getCurrentTenantId(),
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_value' => $unitCost ? $quantity * $unitCost : null,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'created_by' => auth()->id(),
        ]);

        return $movement->id;
    }

    public function getMovementHistory(
        string $productId,
        ?string $warehouseId = null,
        ?int $limit = null
    ): array {
        $query = StockMovement::where('product_id', $productId)
            ->orderBy('created_at', 'desc');

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get()->all();
    }
}
