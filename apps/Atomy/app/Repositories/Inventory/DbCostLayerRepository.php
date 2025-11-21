<?php

declare(strict_types=1);

namespace App\Repositories\Inventory;

use App\Models\CostLayer;
use Nexus\Inventory\Contracts\CostLayerStorageInterface;

final class DbCostLayerRepository implements CostLayerStorageInterface
{
    public function addLayer(string $productId, float $quantity, float $unitCost, \DateTimeImmutable $receivedDate, ?string $referenceId = null): string
    {
        $layer = CostLayer::create([
            'tenant_id' => app('tenant.context')->getCurrentTenantId(),
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'remaining_quantity' => $quantity,
            'received_date' => $receivedDate->format('Y-m-d'),
            'receipt_reference_id' => $referenceId,
        ]);

        return $layer->id;
    }

    public function getOldestLayers(string $productId, int $limit = 10): array
    {
        return CostLayer::where('product_id', $productId)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('received_date', 'ASC')
            ->orderBy('created_at', 'ASC')
            ->limit($limit)
            ->get()
            ->map(fn($layer) => [
                'id' => $layer->id,
                'quantity' => (float) $layer->quantity,
                'unit_cost' => (float) $layer->unit_cost,
                'remaining_quantity' => (float) $layer->remaining_quantity,
                'received_date' => $layer->received_date->toIso8601String(),
            ])
            ->all();
    }

    public function consumeLayer(string $layerId, float $quantity): void
    {
        $layer = CostLayer::find($layerId);
        
        if ($layer) {
            $layer->remaining_quantity = bcsub((string) $layer->remaining_quantity, (string) $quantity, 4);
            $layer->save();
        }
    }

    public function getTotalRemainingCost(string $productId): float
    {
        $layers = CostLayer::where('product_id', $productId)
            ->where('remaining_quantity', '>', 0)
            ->get();

        $total = 0.0;
        foreach ($layers as $layer) {
            $total += (float) $layer->remaining_quantity * (float) $layer->unit_cost;
        }

        return $total;
    }
}
