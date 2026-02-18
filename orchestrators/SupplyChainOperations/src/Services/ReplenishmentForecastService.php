<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Services;

use DateTimeImmutable;
use Nexus\Inventory\Contracts\InventoryAnalyticsRepositoryInterface;
use Nexus\Inventory\MachineLearning\DemandForecastExtractor;
use Nexus\Product\Contracts\ProductVariantRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for calculating demand forecasts and replenishment recommendations.
 *
 * Provides ML-based forecasting for inventory replenishment decisions,
 * including dynamic reorder points and safety stock calculations.
 */
final readonly class ReplenishmentForecastService
{
    public function __construct(
        private InventoryAnalyticsRepositoryInterface $analyticsRepository,
        private ProductVariantRepositoryInterface $productRepository,
        private ?LoggerInterface $logger = null
    ) {
    }

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
    ): ?array {
        try {
            $extractor = $this->createExtractor();
            $productEntity = $this->createProductEntity($productId);

            $featureSet = $extractor->extract($productEntity);
            $features = $featureSet->toArray();

            $dynamicReorderPoint = $features['reorder_point_recommendation'];
            $safetyStock = $features['safety_stock_recommendation'];
            $forecast30d = $features['forecasted_demand_30d'];
            $forecast7d = $features['forecasted_demand_7d'];

            $suggestedQty = $this->calculateSuggestedQuantity(
                $forecast30d,
                $currentStock,
                $safetyStock
            );

            return [
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'current_stock' => $currentStock,
                'reorder_point' => $dynamicReorderPoint,
                'safety_stock' => $safetyStock,
                'forecast_30d' => $forecast30d,
                'forecast_7d' => $forecast7d,
                'suggested_qty' => $suggestedQty,
                'requires_reorder' => $currentStock <= $dynamicReorderPoint && $suggestedQty > 0,
                'confidence_factors' => [
                    'volatility' => $features['demand_volatility_coefficient'],
                    'trend' => $features['trend_slope_30d'],
                    'seasonality' => $features['seasonality_index'],
                    'lead_time_days' => $features['supplier_lead_time_days'],
                    'lead_time_variability' => $features['lead_time_variability'],
                ],
            ];
        } catch (\Throwable $e) {
            $this->logError("Failed to evaluate product {$productId} with forecast: " . $e->getMessage());
            return null;
        }
    }

    private function createExtractor(): DemandForecastExtractor
    {
        return new DemandForecastExtractor(
            $this->analyticsRepository,
            $this->productRepository
        );
    }

    private function createProductEntity(string $productId): object
    {
        $createdAt = new DateTimeImmutable();

        try {
            $product = $this->productRepository->findById($productId);
            if ($product !== null && method_exists($product, 'getCreatedAt')) {
                $createdAt = $product->getCreatedAt();
            }
        } catch (\Throwable $e) {
            // Use current date as fallback
        }

        return (object) [
            'product_id' => $productId,
            'created_at' => $createdAt,
        ];
    }

    private function calculateSuggestedQuantity(
        float $forecast30d,
        float $currentStock,
        float $safetyStock
    ): float {
        return max($forecast30d - $currentStock + $safetyStock, 0);
    }

    private function logError(string $message): void
    {
        if ($this->logger !== null) {
            $this->logger->error($message);
        }
    }
}
