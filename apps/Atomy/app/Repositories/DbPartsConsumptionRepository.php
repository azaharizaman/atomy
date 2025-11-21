<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PartsConsumption;
use Nexus\FieldService\Contracts\PartsConsumptionInterface;
use Nexus\FieldService\Contracts\PartsConsumptionRepositoryInterface;

final readonly class DbPartsConsumptionRepository implements PartsConsumptionRepositoryInterface
{
    public function __construct() {}

    public function save(PartsConsumptionInterface $partsConsumption): void
    {
        if ($partsConsumption instanceof PartsConsumption) {
            $partsConsumption->save();
            return;
        }

        throw new \InvalidArgumentException('PartsConsumption must be an Eloquent model');
    }

    public function getByWorkOrder(string $workOrderId): array
    {
        return PartsConsumption::forWorkOrder($workOrderId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function getTotalQuantity(string $workOrderId, string $productVariantId): float
    {
        return (float) PartsConsumption::forWorkOrder($workOrderId)
            ->where('product_variant_id', $productVariantId)
            ->sum('quantity');
    }

    public function getConsumedFromVan(string $workOrderId): array
    {
        return PartsConsumption::forWorkOrder($workOrderId)
            ->fromVan()
            ->get()
            ->all();
    }

    public function getConsumedFromWarehouse(string $workOrderId): array
    {
        return PartsConsumption::forWorkOrder($workOrderId)
            ->fromWarehouse()
            ->get()
            ->all();
    }
}
