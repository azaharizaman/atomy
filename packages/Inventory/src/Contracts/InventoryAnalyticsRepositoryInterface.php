<?php

declare(strict_types=1);

namespace Nexus\Inventory\Contracts;

/**
 * Inventory analytics repository interface
 * 
 * Provides time-series and aggregated data for demand forecasting and inventory optimization.
 */
interface InventoryAnalyticsRepositoryInterface
{
    /**
     * Get average daily demand for product over period
     * 
     * @param string $productId Product identifier
     * @param int $days Number of days to analyze
     * @return float Average daily demand (units)
     */
    public function getAverageDailyDemand(string $productId, int $days): float;
    
    /**
     * Get demand volatility coefficient
     * 
     * @param string $productId Product identifier
     * @param int $days Number of days to analyze
     * @return float Coefficient of variation (stddev/mean)
     */
    public function getDemandVolatilityCoefficient(string $productId, int $days): float;
    
    /**
     * Get seasonality index for product
     * 
     * @param string $productId Product identifier
     * @return float Seasonality multiplier (current month avg / yearly avg)
     */
    public function getSeasonalityIndex(string $productId): float;
    
    /**
     * Get demand trend slope (linear regression)
     * 
     * @param string $productId Product identifier
     * @param int $days Number of days for trend calculation
     * @return float Trend slope (units per day, positive = growing demand)
     */
    public function getTrendSlope(string $productId, int $days): float;
    
    /**
     * Get recent sales quantities
     * 
     * @param string $productId Product identifier
     * @param int $days Number of days to look back
     * @return float Total units sold
     */
    public function getRecentSales(string $productId, int $days): float;
    
    /**
     * Get stockout days count
     * 
     * @param string $productId Product identifier
     * @param int $days Number of days to analyze
     * @return int Number of days with zero stock
     */
    public function getStockoutDays(string $productId, int $days): int;
    
    /**
     * Get backorder count
     * 
     * @param string $productId Product identifier
     * @param int $days Number of days to look back
     * @return int Number of backorders
     */
    public function getBackorderCount(string $productId, int $days): int;
    
    /**
     * Check if product has active promotion
     * 
     * @param string $productId Product identifier
     * @return bool True if promotion active
     */
    public function hasActivePromotion(string $productId): bool;
    
    /**
     * Get price change percentage in last period
     * 
     * @param string $productId Product identifier
     * @param int $days Number of days to look back
     * @return float Percentage change (positive = price increase)
     */
    public function getPriceChangePercentage(string $productId, int $days): float;
    
    /**
     * Get product lifecycle stage
     * 
     * @param string $productId Product identifier
     * @return string Stage: 'introduction', 'growth', 'mature', 'decline'
     */
    public function getProductLifecycleStage(string $productId): string;
    
    /**
     * Get supplier lead time in days
     * 
     * @param string $productId Product identifier
     * @return int Average lead time days
     */
    public function getSupplierLeadTimeDays(string $productId): int;
    
    /**
     * Get lead time variability (standard deviation)
     * 
     * @param string $productId Product identifier
     * @return float Standard deviation of lead times in days
     */
    public function getLeadTimeVariability(string $productId): float;
    
    /**
     * Get current safety stock level
     * 
     * @param string $productId Product identifier
     * @return float Safety stock quantity
     */
    public function getCurrentSafetyStock(string $productId): float;
}
