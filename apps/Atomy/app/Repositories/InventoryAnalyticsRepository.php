<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Nexus\Inventory\Contracts\InventoryAnalyticsRepositoryInterface;

/**
 * Eloquent + Raw SQL implementation of InventoryAnalyticsRepositoryInterface.
 * 
 * Queries the mv_product_demand_analytics materialized view for DemandForecastExtractor.
 * All queries are tenant-scoped for security and partition pruning optimization.
 */
final readonly class InventoryAnalyticsRepository implements InventoryAnalyticsRepositoryInterface
{
    public function __construct(
        private string $tenantId
    ) {}

    public function getAverageDailyDemand30d(string $productId): float
    {
        $result = DB::selectOne(
            "SELECT avg_daily_demand_30d 
             FROM mv_product_demand_analytics 
             WHERE tenant_id = ? AND product_id = ?",
            [$this->tenantId, $productId]
        );

        return $result?->avg_daily_demand_30d ?? 0.0;
    }

    public function getAverageDailyDemand90d(string $productId): float
    {
        $result = DB::selectOne(
            "SELECT avg_daily_demand_90d 
             FROM mv_product_demand_analytics 
             WHERE tenant_id = ? AND product_id = ?",
            [$this->tenantId, $productId]
        );

        return $result?->avg_daily_demand_90d ?? 0.0;
    }

    public function getAverageDailyDemand365d(string $productId): float
    {
        $result = DB::selectOne(
            "SELECT avg_daily_demand_365d 
             FROM mv_product_demand_analytics 
             WHERE tenant_id = ? AND product_id = ?",
            [$this->tenantId, $productId]
        );

        return $result?->avg_daily_demand_365d ?? 0.0;
    }

    public function getDemandStdDev30d(string $productId): float
    {
        $result = DB::selectOne(
            "SELECT demand_std_dev_30d 
             FROM mv_product_demand_analytics 
             WHERE tenant_id = ? AND product_id = ?",
            [$this->tenantId, $productId]
        );

        return $result?->demand_std_dev_30d ?? 0.0;
    }

    public function getDemandStdDev90d(string $productId): float
    {
        $result = DB::selectOne(
            "SELECT demand_std_dev_90d 
             FROM mv_product_demand_analytics 
             WHERE tenant_id = ? AND product_id = ?",
            [$this->tenantId, $productId]
        );

        return $result?->demand_std_dev_90d ?? 0.0;
    }

    public function getMaxDailyDemand30d(string $productId): float
    {
        $result = DB::selectOne(
            "SELECT max_daily_demand_30d 
             FROM mv_product_demand_analytics 
             WHERE tenant_id = ? AND product_id = ?",
            [$this->tenantId, $productId]
        );

        return $result?->max_daily_demand_30d ?? 0.0;
    }

    public function getStockoutDays30d(string $productId): int
    {
        $result = DB::selectOne(
            "SELECT stockout_days_30d 
             FROM mv_product_demand_analytics 
             WHERE tenant_id = ? AND product_id = ?",
            [$this->tenantId, $productId]
        );

        return $result?->stockout_days_30d ?? 0;
    }

    public function getZeroDemandDays30d(string $productId): int
    {
        $result = DB::selectOne(
            "SELECT zero_demand_days_30d 
             FROM mv_product_demand_analytics 
             WHERE tenant_id = ? AND product_id = ?",
            [$this->tenantId, $productId]
        );

        return $result?->zero_demand_days_30d ?? 0;
    }

    public function getDemandTrendSlope(string $productId): float
    {
        $result = DB::selectOne(
            "SELECT demand_trend_slope 
             FROM mv_product_demand_analytics 
             WHERE tenant_id = ? AND product_id = ?",
            [$this->tenantId, $productId]
        );

        return $result?->demand_trend_slope ?? 0.0;
    }

    public function getSeasonalityIndex(string $productId): float
    {
        $result = DB::selectOne(
            "SELECT seasonality_index 
             FROM mv_product_demand_analytics 
             WHERE tenant_id = ? AND product_id = ?",
            [$this->tenantId, $productId]
        );

        return $result?->seasonality_index ?? 1.0;
    }

    public function getCoefficientOfVariation(string $productId): float
    {
        $result = DB::selectOne(
            "SELECT coefficient_of_variation 
             FROM mv_product_demand_analytics 
             WHERE tenant_id = ? AND product_id = ?",
            [$this->tenantId, $productId]
        );

        return $result?->coefficient_of_variation ?? 0.0;
    }

    public function getDaysSinceLastSale(string $productId): int
    {
        $result = DB::selectOne(
            "SELECT days_since_last_sale 
             FROM mv_product_demand_analytics 
             WHERE tenant_id = ? AND product_id = ?",
            [$this->tenantId, $productId]
        );

        return $result?->days_since_last_sale ?? 0;
    }

    public function getSalesVelocityRatio(string $productId): float
    {
        $result = DB::selectOne(
            "SELECT sales_velocity_ratio 
             FROM mv_product_demand_analytics 
             WHERE tenant_id = ? AND product_id = ?",
            [$this->tenantId, $productId]
        );

        return $result?->sales_velocity_ratio ?? 0.0;
    }

    public function getCurrentStockLevel(string $productId): float
    {
        $result = DB::selectOne(
            "SELECT current_stock_level 
             FROM mv_product_demand_analytics 
             WHERE tenant_id = ? AND product_id = ?",
            [$this->tenantId, $productId]
        );

        return $result?->current_stock_level ?? 0.0;
    }

    public function getDaysOfInventoryOnHand(string $productId): float
    {
        $result = DB::selectOne(
            "SELECT days_of_inventory_on_hand 
             FROM mv_product_demand_analytics 
             WHERE tenant_id = ? AND product_id = ?",
            [$this->tenantId, $productId]
        );

        return $result?->days_of_inventory_on_hand ?? 0.0;
    }

    public function getInventoryTurnoverRatio(string $productId): float
    {
        $result = DB::selectOne(
            "SELECT inventory_turnover_ratio 
             FROM mv_product_demand_analytics 
             WHERE tenant_id = ? AND product_id = ?",
            [$this->tenantId, $productId]
        );

        return $result?->inventory_turnover_ratio ?? 0.0;
    }

    public function getReorderPoint(string $productId): float
    {
        $result = DB::selectOne(
            "SELECT reorder_point 
             FROM mv_product_demand_analytics 
             WHERE tenant_id = ? AND product_id = ?",
            [$this->tenantId, $productId]
        );

        return $result?->reorder_point ?? 0.0;
    }

    public function getEconomicOrderQuantity(string $productId): float
    {
        $result = DB::selectOne(
            "SELECT economic_order_quantity 
             FROM mv_product_demand_analytics 
             WHERE tenant_id = ? AND product_id = ?",
            [$this->tenantId, $productId]
        );

        return $result?->economic_order_quantity ?? 0.0;
    }

    public function getActiveProducts(?int $limit = null): Collection
    {
        $query = "SELECT p.id, p.sku, p.name, p.status, a.current_stock_level
                  FROM products p
                  LEFT JOIN mv_product_demand_analytics a 
                    ON a.tenant_id = p.tenant_id AND a.product_id = p.id
                  WHERE p.tenant_id = ?
                    AND p.status NOT IN ('obsolete', 'discontinued')
                    AND (a.days_since_last_sale IS NULL OR a.days_since_last_sale < 365)
                  ORDER BY p.sku ASC";

        if ($limit !== null) {
            $query .= " LIMIT {$limit}";
        }

        $results = DB::select($query, [$this->tenantId]);

        return collect($results)->map(function ($row) {
            return (object) [
                'id' => $row->id,
                'sku' => $row->sku,
                'name' => $row->name,
                'status' => $row->status,
                'current_stock' => $row->current_stock_level ?? 0.0,
            ];
        });
    }

    /**
     * Refresh analytics for a specific product (called after stock movement).
     * 
     * This marks the product as dirty, triggering incremental refresh on next scheduled run.
     */
    public function refreshProductAnalytics(string $productId): void
    {
        DB::insert(
            "INSERT INTO mv_product_demand_analytics_dirty (tenant_id, product_id, marked_at)
             VALUES (?, ?, CURRENT_TIMESTAMP)
             ON CONFLICT (tenant_id, product_id) DO UPDATE SET marked_at = CURRENT_TIMESTAMP",
            [$this->tenantId, $productId]
        );
    }
}
