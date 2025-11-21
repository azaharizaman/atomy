<?php

declare(strict_types=1);

namespace App\Repositories\Inventory;

use App\Models\Lot;
use App\Models\LotStockLevel;
use Nexus\Inventory\Contracts\LotRepositoryInterface;

final class DbLotRepository implements LotRepositoryInterface
{
    public function findById(string $id): ?array
    {
        $lot = Lot::find($id);
        return $lot ? $this->toArray($lot) : null;
    }

    public function findByLotNumber(string $tenantId, string $lotNumber): ?array
    {
        $lot = Lot::where('tenant_id', $tenantId)
            ->where('lot_number', $lotNumber)
            ->first();

        return $lot ? $this->toArray($lot) : null;
    }

    public function findByProductAndWarehouse(
        string $productId,
        string $warehouseId,
        string $orderBy = 'expiry_date',
        string $direction = 'ASC'
    ): array {
        return Lot::where('product_id', $productId)
            ->where('is_active', true)
            ->whereHas('stockLevels', function ($query) use ($warehouseId) {
                $query->where('warehouse_id', $warehouseId)
                    ->where('quantity', '>', 0);
            })
            ->with(['stockLevels' => function ($query) use ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            }])
            ->orderBy($orderBy, $direction)
            ->get()
            ->map(fn($lot) => $this->toArray($lot))
            ->all();
    }

    public function create(array $data): string
    {
        $lot = Lot::create([
            'tenant_id' => $data['tenant_id'],
            'lot_number' => $data['lot_number'],
            'product_id' => $data['product_id'],
            'expiry_date' => $data['expiry_date'],
            'manufacturing_date' => $data['manufacturing_date'] ?? null,
            'supplier_lot_number' => $data['supplier_lot_number'] ?? null,
        ]);

        return $lot->id;
    }

    public function updateQuantity(string $lotId, string $warehouseId, float $quantityChange): void
    {
        $lotLevel = LotStockLevel::firstOrNew([
            'lot_id' => $lotId,
            'warehouse_id' => $warehouseId,
        ], [
            'quantity' => 0,
        ]);

        $lotLevel->quantity = bcadd((string) $lotLevel->quantity, (string) $quantityChange, 4);
        $lotLevel->save();
    }

    private function toArray(Lot $lot): array
    {
        $stockLevel = $lot->stockLevels->first();

        return [
            'id' => $lot->id,
            'lot_number' => $lot->lot_number,
            'product_id' => $lot->product_id,
            'expiry_date' => $lot->expiry_date,
            'manufacturing_date' => $lot->manufacturing_date,
            'quantity' => $stockLevel ? (float) $stockLevel->quantity : 0.0,
            'is_active' => $lot->is_active,
        ];
    }
}
