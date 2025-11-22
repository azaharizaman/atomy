<?php

declare(strict_types=1);

namespace Nexus\Inventory\Intelligence;

use DateTimeImmutable;
use Nexus\Intelligence\Contracts\FeatureExtractorInterface;
use Nexus\Intelligence\Contracts\FeatureSetInterface;
use Nexus\Intelligence\ValueObjects\FeatureSet;
use Nexus\Inventory\Contracts\InventoryAnalyticsRepositoryInterface;
use Nexus\Inventory\Contracts\ProductRepositoryInterface;

/**
 * Demand forecast extractor
 * 
 * Extracts 22 features to predict future product demand for inventory planning.
 * Uses historical sales patterns, seasonality, promotions, and lead time data.
 * 
 * Schema v1.0 - Initial implementation
 */
final readonly class DemandForecastExtractor implements FeatureExtractorInterface
{
    private const SCHEMA_VERSION = '1.0';
    
    // Lifecycle stage thresholds (months)
    private const LIFECYCLE_INTRO_MONTHS = 6;
    private const LIFECYCLE_GROWTH_MONTHS = 24;
    private const LIFECYCLE_MATURE_MONTHS = 60;
    
    public function __construct(
        private InventoryAnalyticsRepositoryInterface $analytics,
        private ProductRepositoryInterface $productRepository
    ) {}
    
    /**
     * {@inheritDoc}
     */
    public function extract(object $entity): FeatureSetInterface
    {
        // Expected entity: product with product_id, created_at, etc.
        $productId = $entity->product_id ?? $entity->id ?? throw new \InvalidArgumentException('Missing product_id');
        
        // Get product details
        $product = $this->productRepository->findById($productId);
        if ($product === null) {
            throw new \InvalidArgumentException("Product {$productId} not found");
        }
        
        $productAgeMonths = $this->calculateProductAge($product->getCreatedAt());
        
        $features = [
            // Historical Sales (6 features)
            'avg_daily_demand_30d' => $this->analytics->getAverageDailyDemand($productId, 30),
            'avg_daily_demand_90d' => $this->analytics->getAverageDailyDemand($productId, 90),
            'avg_daily_demand_365d' => $this->analytics->getAverageDailyDemand($productId, 365),
            'demand_volatility_coefficient' => $this->analytics->getDemandVolatilityCoefficient($productId, 90),
            'seasonality_index' => $this->analytics->getSeasonalityIndex($productId),
            'trend_slope_30d' => $this->analytics->getTrendSlope($productId, 30),
            
            // Recent Activity (3 features)
            'sales_last_7d' => $this->analytics->getRecentSales($productId, 7),
            'sales_last_14d' => $this->analytics->getRecentSales($productId, 14),
            'sales_last_30d' => $this->analytics->getRecentSales($productId, 30),
            
            // Stockout History (2 features)
            'stockout_days_last_90d' => (float) $this->analytics->getStockoutDays($productId, 90),
            'backorder_count_last_30d' => (float) $this->analytics->getBackorderCount($productId, 30),
            
            // Promotional (2 features)
            'promotion_active_flag' => $this->analytics->hasActivePromotion($productId) ? 1.0 : 0.0,
            'price_change_pct_last_30d' => $this->analytics->getPriceChangePercentage($productId, 30),
            
            // Product Lifecycle (2 features)
            'product_age_months' => (float) $productAgeMonths,
            'lifecycle_stage' => $this->encodeLifecycleStage(
                $this->analytics->getProductLifecycleStage($productId)
            ),
            
            // Lead Time (2 features)
            'supplier_lead_time_days' => (float) $this->analytics->getSupplierLeadTimeDays($productId),
            'lead_time_variability' => $this->analytics->getLeadTimeVariability($productId),
            
            // Current Stock (1 feature)
            'safety_stock_current' => $this->analytics->getCurrentSafetyStock($productId),
            
            // Engineered Features (4 features)
            'forecasted_demand_7d' => 0.0, // Calculated below
            'forecasted_demand_30d' => 0.0, // Calculated below
            'reorder_point_recommendation' => 0.0, // Calculated below
            'safety_stock_recommendation' => 0.0, // Calculated below
        ];
        
        // Calculate forecasts (simple baseline model using trend + seasonality)
        $baseDemand = $features['avg_daily_demand_30d'];
        $trendAdjustment = $features['trend_slope_30d'] * 7.0; // 7-day trend impact
        $seasonalMultiplier = $features['seasonality_index'];
        
        $features['forecasted_demand_7d'] = max(
            ($baseDemand * 7.0 + $trendAdjustment) * $seasonalMultiplier,
            0.0
        );
        
        $features['forecasted_demand_30d'] = max(
            ($baseDemand * 30.0 + ($features['trend_slope_30d'] * 30.0)) * $seasonalMultiplier,
            0.0
        );
        
        // Calculate reorder point: (avg demand × lead time) + safety stock
        $leadTimeDemand = $baseDemand * $features['supplier_lead_time_days'];
        $safetyStock = $this->calculateSafetyStock(
            $baseDemand,
            $features['demand_volatility_coefficient'],
            $features['supplier_lead_time_days'],
            $features['lead_time_variability']
        );
        
        $features['reorder_point_recommendation'] = $leadTimeDemand + $safetyStock;
        $features['safety_stock_recommendation'] = $safetyStock;
        
        $metadata = [
            'entity_type' => 'product',
            'product_id' => $productId,
            'product_code' => $product->getCode(),
            'extracted_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
        
        return new FeatureSet($features, self::SCHEMA_VERSION, $metadata);
    }
    
    /**
     * {@inheritDoc}
     */
    public function getFeatureKeys(): array
    {
        return [
            'avg_daily_demand_30d',
            'avg_daily_demand_90d',
            'avg_daily_demand_365d',
            'demand_volatility_coefficient',
            'seasonality_index',
            'trend_slope_30d',
            'sales_last_7d',
            'sales_last_14d',
            'sales_last_30d',
            'stockout_days_last_90d',
            'backorder_count_last_30d',
            'promotion_active_flag',
            'price_change_pct_last_30d',
            'product_age_months',
            'lifecycle_stage',
            'supplier_lead_time_days',
            'lead_time_variability',
            'safety_stock_current',
            'forecasted_demand_7d',
            'forecasted_demand_30d',
            'reorder_point_recommendation',
            'safety_stock_recommendation',
        ];
    }
    
    /**
     * {@inheritDoc}
     */
    public function getSchemaVersion(): string
    {
        return self::SCHEMA_VERSION;
    }
    
    /**
     * Calculate product age in months
     * 
     * @param DateTimeImmutable $createdAt Product creation date
     * @return int Age in months
     */
    private function calculateProductAge(DateTimeImmutable $createdAt): int
    {
        $now = new DateTimeImmutable();
        $diff = $now->diff($createdAt);
        
        return ($diff->y * 12) + $diff->m;
    }
    
    /**
     * Encode lifecycle stage as numeric value
     * 
     * @param string $stage Lifecycle stage
     * @return float Encoded value 1.0-4.0
     */
    private function encodeLifecycleStage(string $stage): float
    {
        return match ($stage) {
            'introduction' => 1.0,
            'growth' => 2.0,
            'mature' => 3.0,
            'decline' => 4.0,
            default => 3.0, // Default to mature
        };
    }
    
    /**
     * Calculate safety stock using standard formula
     * 
     * Safety Stock = Z × σD × √LT
     * where Z = service level factor (1.65 for 95% service level)
     * 
     * @param float $avgDailyDemand Average daily demand
     * @param float $demandVolatility Coefficient of variation
     * @param float $leadTimeDays Lead time in days
     * @param float $leadTimeVariability Lead time standard deviation
     * @return float Safety stock quantity
     */
    private function calculateSafetyStock(
        float $avgDailyDemand,
        float $demandVolatility,
        float $leadTimeDays,
        float $leadTimeVariability
    ): float {
        // Service level Z-score (95% = 1.65, 99% = 2.33)
        $zScore = 1.65;
        
        // Demand standard deviation
        $demandStdDev = $avgDailyDemand * $demandVolatility;
        
        // Safety stock formula accounting for both demand and lead time variability
        $demandComponent = $demandStdDev * sqrt($leadTimeDays);
        $leadTimeComponent = $avgDailyDemand * $leadTimeVariability;
        
        return $zScore * sqrt(($demandComponent ** 2) + ($leadTimeComponent ** 2));
    }
}
