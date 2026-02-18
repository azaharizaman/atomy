<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Contracts;

interface ReplenishmentForecastServiceInterface
{
    /**
     * Evaluate a product's replenishment needs using ML-based forecasting.
     *
     * @return array{
     *     product_id: string,
     *     warehouse_id: string,
     *     current_stock: float,
     *     reorder_point: float,
     *     safety_stock: float,
     *     forecast_30d: float,
     *     forecast_7d: float,
     *     suggested_qty: float,
     *     confidence_factors: array,
     *     requires_reorder: bool
     * }|null
     */
    public function evaluateWithForecast(
        string $productId,
        string $warehouseId,
        float $currentStock
    ): ?array;
}
