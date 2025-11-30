<?php

declare(strict_types=1);

namespace Nexus\Laravel\Inventory\Repositories;

use Illuminate\Support\Facades\DB;
use Nexus\Inventory\Contracts\InventoryAnalyticsRepositoryInterface;
use Nexus\Inventory\Enums\MovementType;
use Nexus\Laravel\Inventory\Models\StockLevel;
use Nexus\Laravel\Inventory\Models\StockMovement;

/**
 * Eloquent implementation of Inventory Analytics Repository
 * 
 * Provides time-series and aggregated data for demand forecasting and inventory optimization.
 */
final readonly class EloquentInventoryAnalyticsRepository implements InventoryAnalyticsRepositoryInterface
{
    public function getAverageDailyDemand(string $productId, int $days): float
    {
        // For demand calculations, we sum ISSUE movements.
        // Quantities should be positive in our data model (direction is determined by movement type).
        $totalDemand = StockMovement::where('product_id', $productId)
            ->where('movement_type', MovementType::ISSUE->value)
            ->where('created_at', '>=', now()->subDays($days))
            ->sum('quantity');

        if ($days <= 0) {
            return 0.0;
        }

        // Return absolute value to ensure positive demand even if data has inconsistencies
        $result = bcdiv((string) $totalDemand, (string) $days, 4);
        return abs((float) $result);
    }

    public function getDemandVolatilityCoefficient(string $productId, int $days): float
    {
        // Sum daily demand - quantities should be positive in our data model
        $dailyDemands = StockMovement::where('product_id', $productId)
            ->where('movement_type', MovementType::ISSUE->value)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, SUM(quantity) as daily_quantity')
            ->groupBy('date')
            ->pluck('daily_quantity')
            ->map(fn ($q) => abs((float) $q))
            ->all();

        if (count($dailyDemands) === 0) {
            return 0.0;
        }

        $sum = array_reduce($dailyDemands, fn($carry, $item) => bcadd((string) $carry, (string) $item, 10), '0');
        $mean = (float) bcdiv($sum, (string) count($dailyDemands), 10);
        
        if ($mean === 0.0) {
            return 0.0;
        }

        $squaredDiffs = array_map(
            fn($demand) => bcpow(bcsub((string) $demand, (string) $mean, 10), '2', 10),
            $dailyDemands
        );
        $squaredSum = array_reduce($squaredDiffs, fn($carry, $item) => bcadd((string) $carry, (string) $item, 10), '0');
        $variance = (float) bcdiv(
            $squaredSum,
            (string) count($dailyDemands),
            10
        );

        $stdDev = (float) bcsqrt((string) $variance, 10);

        return (float) bcdiv((string) $stdDev, (string) $mean, 10);
    }

    public function getSeasonalityIndex(string $productId): float
    {
        $currentMonth = (int) now()->format('m');
        
        // Get current month's average daily demand
        $currentMonthDemand = $this->getAverageDailyDemand($productId, 30);
        
        // Get yearly average (last 365 days)
        $yearlyAverage = $this->getAverageDailyDemand($productId, 365);
        
        if ($yearlyAverage === 0.0) {
            return 1.0;
        }

        // Seasonality index: current month demand / yearly average
        return (float) bcdiv((string) $currentMonthDemand, (string) $yearlyAverage, 6);
    }

    public function getTrendSlope(string $productId, int $days): float
    {
        // For trend calculation, we track daily demand over time
        // Quantities should be positive in our data model
        $dailyDemands = StockMovement::where('product_id', $productId)
            ->where('movement_type', MovementType::ISSUE->value)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, SUM(quantity) as daily_quantity')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('daily_quantity', 'date')
            ->map(fn ($q) => abs((float) $q))
            ->all();

        if (count($dailyDemands) < 2) {
            return 0.0;
        }

        // Simple linear regression
        $n = count($dailyDemands);
        $x = range(1, $n);
        $y = array_values($dailyDemands);

        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
        }

        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        
        if ($denominator === 0.0) {
            return 0.0;
        }

        // Calculate slope using least squares method with BC Math for precision
        $nStr = (string) $n;
        $numeratorPart1 = bcmul($nStr, (string) $sumXY, 10);
        $numeratorPart2 = bcmul((string) $sumX, (string) $sumY, 10);
        $numerator = bcsub($numeratorPart1, $numeratorPart2, 10);
        
        $denominatorPart1 = bcmul($nStr, (string) $sumX2, 10);
        $denominatorPart2 = bcmul((string) $sumX, (string) $sumX, 10);
        $denominatorBC = bcsub($denominatorPart1, $denominatorPart2, 10);
        
        return (float) bcdiv($numerator, $denominatorBC, 10);
    }

    public function getRecentSales(string $productId, int $days): float
    {
        return (float) abs(StockMovement::where('product_id', $productId)
            ->where('movement_type', MovementType::ISSUE->value)
            ->where('created_at', '>=', now()->subDays($days))
            ->sum('quantity'));
    }

    public function getStockoutDays(string $productId, int $days): int
    {
        // This requires historical stock level snapshots
        // For now, we count days with zero stock movements as proxy
        $daysWithMovements = StockMovement::where('product_id', $productId)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(created_at) as date')
            ->distinct()
            ->count();

        return max(0, $days - $daysWithMovements);
    }

    public function getBackorderCount(string $productId, int $days): int
    {
        // This would typically come from order data
        // Return 0 as placeholder - actual implementation depends on order integration
        return 0;
    }

    public function hasActivePromotion(string $productId): bool
    {
        // This would typically come from marketing/pricing package
        // Return false as placeholder
        return false;
    }

    public function getPriceChangePercentage(string $productId, int $days): float
    {
        // Get average unit cost from movements in the period
        $recentAvgCost = StockMovement::where('product_id', $productId)
            ->where('created_at', '>=', now()->subDays($days))
            ->where('unit_cost', '>', 0)
            ->avg('unit_cost');

        $previousAvgCost = StockMovement::where('product_id', $productId)
            ->where('created_at', '>=', now()->subDays($days * 2))
            ->where('created_at', '<', now()->subDays($days))
            ->where('unit_cost', '>', 0)
            ->avg('unit_cost');

        if ($previousAvgCost === null || (float) $previousAvgCost === 0.0) {
            return 0.0;
        }

        $recentAvgCost = (float) ($recentAvgCost ?? $previousAvgCost);
        $previousAvgCost = (float) $previousAvgCost;

        $difference = bcsub((string) $recentAvgCost, (string) $previousAvgCost, 10);
        $ratio = bcdiv($difference, (string) $previousAvgCost, 10);
        return (float) bcmul($ratio, '100', 6);
    }

    public function getProductLifecycleStage(string $productId): string
    {
        $trend = $this->getTrendSlope($productId, 90);
        $volatility = $this->getDemandVolatilityCoefficient($productId, 90);
        
        // Simple heuristic for lifecycle stage
        if ($volatility > 0.5) {
            return 'introduction';
        }
        
        if ($trend > 0.1) {
            return 'growth';
        }
        
        if ($trend < -0.1) {
            return 'decline';
        }
        
        return 'mature';
    }

    public function getSupplierLeadTimeDays(string $productId): int
    {
        // This would typically come from procurement data
        // Return default as placeholder
        return 7;
    }

    public function getLeadTimeVariability(string $productId): float
    {
        // This would typically come from procurement data
        // Return default as placeholder
        return 2.0;
    }

    public function getCurrentSafetyStock(string $productId): float
    {
        // Calculate based on demand volatility and lead time
        $avgDailyDemand = $this->getAverageDailyDemand($productId, 30);
        $volatility = $this->getDemandVolatilityCoefficient($productId, 30);
        $leadTime = $this->getSupplierLeadTimeDays($productId);
        
        // Z-score for 95% service level = 1.65
        $zScore = 1.65;
        
        return $zScore * $volatility * $avgDailyDemand * sqrt($leadTime);
    }
}
