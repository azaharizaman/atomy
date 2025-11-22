<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the partitioned materialized view for product demand analytics.
     * Used by DemandForecastExtractor for inventory optimization.
     *
     * Architecture:
     * - List partitioned by tenant_id (auto-provisioned via TenantCreatedEvent listener)
     * - Incremental refresh every hour via dirty_records tracking
     * - UNLOGGED dirty table for write performance
     */
    public function up(): void
    {
        // Create partitioned materialized view parent table
        DB::statement("
            CREATE TABLE mv_product_demand_analytics (
                tenant_id VARCHAR(26) NOT NULL,
                product_id VARCHAR(26) NOT NULL,
                avg_daily_demand_30d DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                avg_daily_demand_90d DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                avg_daily_demand_365d DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                demand_std_dev_30d DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                demand_std_dev_90d DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                max_daily_demand_30d DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                stockout_days_30d INTEGER NOT NULL DEFAULT 0,
                zero_demand_days_30d INTEGER NOT NULL DEFAULT 0,
                demand_trend_slope DECIMAL(10, 4) NOT NULL DEFAULT 0.0000,
                seasonality_index DECIMAL(5, 2) NOT NULL DEFAULT 1.00,
                coefficient_of_variation DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
                days_since_last_sale INTEGER NOT NULL DEFAULT 0,
                sales_velocity_ratio DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
                current_stock_level DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                days_of_inventory_on_hand DECIMAL(5, 1) NOT NULL DEFAULT 0.0,
                inventory_turnover_ratio DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
                reorder_point DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                economic_order_quantity DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                lead_time_days INTEGER NOT NULL DEFAULT 0,
                minimum_order_quantity DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                is_perishable BOOLEAN NOT NULL DEFAULT false,
                shelf_life_days INTEGER,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (tenant_id, product_id)
            ) PARTITION BY LIST (tenant_id)
        ");

        // Create indexes on parent table (inherited by partitions)
        DB::statement("
            CREATE INDEX idx_mv_product_demand_avg_30d ON mv_product_demand_analytics (avg_daily_demand_30d DESC)
        ");
        
        DB::statement("
            CREATE INDEX idx_mv_product_demand_stockout ON mv_product_demand_analytics (stockout_days_30d DESC)
        ");

        // Create dirty records tracking table (UNLOGGED for performance)
        DB::statement("
            CREATE UNLOGGED TABLE mv_product_demand_analytics_dirty (
                tenant_id VARCHAR(26) NOT NULL,
                product_id VARCHAR(26) NOT NULL,
                marked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (tenant_id, product_id)
            )
        ");

        // Create trigger function to mark dirty records on stock_movements changes
        DB::statement("
            CREATE OR REPLACE FUNCTION mark_product_demand_analytics_dirty()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO mv_product_demand_analytics_dirty (tenant_id, product_id)
                VALUES (COALESCE(NEW.tenant_id, OLD.tenant_id), COALESCE(NEW.product_id, OLD.product_id))
                ON CONFLICT (tenant_id, product_id) DO UPDATE SET marked_at = CURRENT_TIMESTAMP;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Attach trigger to stock_movements table
        DB::statement("
            CREATE TRIGGER trg_stock_movements_mark_dirty
            AFTER INSERT OR UPDATE OR DELETE ON stock_movements
            FOR EACH ROW
            EXECUTE FUNCTION mark_product_demand_analytics_dirty()
        ");

        // Create trigger function to mark dirty on sales_order_lines changes
        DB::statement("
            CREATE OR REPLACE FUNCTION mark_product_demand_dirty_from_sales()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO mv_product_demand_analytics_dirty (tenant_id, product_id)
                VALUES (COALESCE(NEW.tenant_id, OLD.tenant_id), COALESCE(NEW.product_id, OLD.product_id))
                ON CONFLICT (tenant_id, product_id) DO UPDATE SET marked_at = CURRENT_TIMESTAMP;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Attach trigger to sales_order_lines table (if exists)
        DB::statement("
            CREATE TRIGGER trg_sales_order_lines_mark_dirty
            AFTER INSERT OR UPDATE OR DELETE ON sales_order_lines
            FOR EACH ROW
            EXECUTE FUNCTION mark_product_demand_dirty_from_sales()
        ");

        // Create stored procedure for incremental refresh (called every hour)
        DB::statement("
            CREATE OR REPLACE PROCEDURE refresh_product_demand_analytics_incremental()
            LANGUAGE plpgsql
            AS $$
            BEGIN
                -- Update existing partitions for dirty records only
                INSERT INTO mv_product_demand_analytics (
                    tenant_id,
                    product_id,
                    avg_daily_demand_30d,
                    avg_daily_demand_90d,
                    avg_daily_demand_365d,
                    demand_std_dev_30d,
                    demand_std_dev_90d,
                    max_daily_demand_30d,
                    stockout_days_30d,
                    zero_demand_days_30d,
                    demand_trend_slope,
                    seasonality_index,
                    coefficient_of_variation,
                    days_since_last_sale,
                    sales_velocity_ratio,
                    current_stock_level,
                    days_of_inventory_on_hand,
                    inventory_turnover_ratio,
                    reorder_point,
                    economic_order_quantity,
                    lead_time_days,
                    minimum_order_quantity,
                    is_perishable,
                    shelf_life_days,
                    updated_at
                )
                SELECT
                    sm.tenant_id,
                    sm.product_id,
                    -- Time-series demand metrics
                    AVG(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '30 days' AND sm.movement_type = 'sales') AS avg_daily_demand_30d,
                    AVG(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '90 days' AND sm.movement_type = 'sales') AS avg_daily_demand_90d,
                    AVG(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '365 days' AND sm.movement_type = 'sales') AS avg_daily_demand_365d,
                    STDDEV(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '30 days' AND sm.movement_type = 'sales') AS demand_std_dev_30d,
                    STDDEV(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '90 days' AND sm.movement_type = 'sales') AS demand_std_dev_90d,
                    MAX(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '30 days' AND sm.movement_type = 'sales') AS max_daily_demand_30d,
                    COUNT(*) FILTER (WHERE sm.current_stock_level = 0 AND sm.movement_date >= CURRENT_DATE - INTERVAL '30 days') AS stockout_days_30d,
                    COUNT(*) FILTER (WHERE ABS(sm.quantity) = 0 AND sm.movement_date >= CURRENT_DATE - INTERVAL '30 days') AS zero_demand_days_30d,
                    
                    -- Trend calculation (linear regression slope)
                    CASE 
                        WHEN COUNT(*) FILTER (WHERE sm.movement_type = 'sales' AND sm.movement_date >= CURRENT_DATE - INTERVAL '90 days') > 1 THEN
                            COALESCE(
                                REGR_SLOPE(ABS(sm.quantity), EXTRACT(EPOCH FROM sm.movement_date)) 
                                FILTER (WHERE sm.movement_type = 'sales' AND sm.movement_date >= CURRENT_DATE - INTERVAL '90 days'),
                                0.0
                            )
                        ELSE 0.0
                    END AS demand_trend_slope,
                    
                    -- Seasonality index (current month vs annual average)
                    CASE 
                        WHEN AVG(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '365 days' AND sm.movement_type = 'sales') > 0 THEN
                            AVG(ABS(sm.quantity)) FILTER (WHERE EXTRACT(MONTH FROM sm.movement_date) = EXTRACT(MONTH FROM CURRENT_DATE) AND sm.movement_type = 'sales') /
                            AVG(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '365 days' AND sm.movement_type = 'sales')
                        ELSE 1.00
                    END AS seasonality_index,
                    
                    -- Coefficient of variation (CV = std_dev / mean)
                    CASE 
                        WHEN AVG(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '90 days' AND sm.movement_type = 'sales') > 0 THEN
                            STDDEV(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '90 days' AND sm.movement_type = 'sales') /
                            AVG(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '90 days' AND sm.movement_type = 'sales')
                        ELSE 0.00
                    END AS coefficient_of_variation,
                    
                    EXTRACT(DAY FROM (CURRENT_DATE - MAX(sm.movement_date) FILTER (WHERE sm.movement_type = 'sales'))) AS days_since_last_sale,
                    
                    -- Sales velocity ratio (30d avg / 90d avg)
                    CASE 
                        WHEN AVG(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '90 days' AND sm.movement_type = 'sales') > 0 THEN
                            AVG(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '30 days' AND sm.movement_type = 'sales') /
                            AVG(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '90 days' AND sm.movement_type = 'sales')
                        ELSE 0.00
                    END AS sales_velocity_ratio,
                    
                    -- Current inventory health
                    COALESCE(MAX(sm.current_stock_level), 0.00) AS current_stock_level,
                    
                    CASE 
                        WHEN AVG(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '30 days' AND sm.movement_type = 'sales') > 0 THEN
                            COALESCE(MAX(sm.current_stock_level), 0) / 
                            AVG(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '30 days' AND sm.movement_type = 'sales')
                        ELSE 0.0
                    END AS days_of_inventory_on_hand,
                    
                    CASE 
                        WHEN AVG(sm.current_stock_level) > 0 THEN
                            SUM(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '365 days' AND sm.movement_type = 'sales') /
                            AVG(sm.current_stock_level)
                        ELSE 0.00
                    END AS inventory_turnover_ratio,
                    
                    -- Reorder point and EOQ calculations
                    (AVG(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '30 days' AND sm.movement_type = 'sales') * 
                     COALESCE(p.lead_time_days, 0)) +
                    (1.65 * STDDEV(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '30 days' AND sm.movement_type = 'sales') * 
                     SQRT(COALESCE(p.lead_time_days, 0))) AS reorder_point,
                    
                    SQRT(
                        (2 * SUM(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '365 days' AND sm.movement_type = 'sales') * 
                         COALESCE(p.ordering_cost, 50)) /
                        COALESCE(p.holding_cost_pct, 0.25)
                    ) AS economic_order_quantity,
                    
                    COALESCE(p.lead_time_days, 0) AS lead_time_days,
                    COALESCE(p.minimum_order_quantity, 0.00) AS minimum_order_quantity,
                    COALESCE(p.is_perishable, false) AS is_perishable,
                    p.shelf_life_days,
                    CURRENT_TIMESTAMP
                FROM stock_movements sm
                JOIN products p ON p.id = sm.product_id
                WHERE EXISTS (
                    SELECT 1 FROM mv_product_demand_analytics_dirty d
                    WHERE d.tenant_id = sm.tenant_id AND d.product_id = sm.product_id
                )
                GROUP BY sm.tenant_id, sm.product_id, p.lead_time_days, p.minimum_order_quantity, 
                         p.is_perishable, p.shelf_life_days, p.ordering_cost, p.holding_cost_pct
                ON CONFLICT (tenant_id, product_id) DO UPDATE SET
                    avg_daily_demand_30d = EXCLUDED.avg_daily_demand_30d,
                    avg_daily_demand_90d = EXCLUDED.avg_daily_demand_90d,
                    avg_daily_demand_365d = EXCLUDED.avg_daily_demand_365d,
                    demand_std_dev_30d = EXCLUDED.demand_std_dev_30d,
                    demand_std_dev_90d = EXCLUDED.demand_std_dev_90d,
                    max_daily_demand_30d = EXCLUDED.max_daily_demand_30d,
                    stockout_days_30d = EXCLUDED.stockout_days_30d,
                    zero_demand_days_30d = EXCLUDED.zero_demand_days_30d,
                    demand_trend_slope = EXCLUDED.demand_trend_slope,
                    seasonality_index = EXCLUDED.seasonality_index,
                    coefficient_of_variation = EXCLUDED.coefficient_of_variation,
                    days_since_last_sale = EXCLUDED.days_since_last_sale,
                    sales_velocity_ratio = EXCLUDED.sales_velocity_ratio,
                    current_stock_level = EXCLUDED.current_stock_level,
                    days_of_inventory_on_hand = EXCLUDED.days_of_inventory_on_hand,
                    inventory_turnover_ratio = EXCLUDED.inventory_turnover_ratio,
                    reorder_point = EXCLUDED.reorder_point,
                    economic_order_quantity = EXCLUDED.economic_order_quantity,
                    lead_time_days = EXCLUDED.lead_time_days,
                    minimum_order_quantity = EXCLUDED.minimum_order_quantity,
                    is_perishable = EXCLUDED.is_perishable,
                    shelf_life_days = EXCLUDED.shelf_life_days,
                    updated_at = EXCLUDED.updated_at;

                -- Clear dirty records
                DELETE FROM mv_product_demand_analytics_dirty;
            END;
            $$
        ");

        // Create stored procedure for full refresh (daily at 1 AM)
        DB::statement("
            CREATE OR REPLACE PROCEDURE refresh_product_demand_analytics_full()
            LANGUAGE plpgsql
            AS $$
            BEGIN
                -- Truncate all partitions and rebuild from scratch
                TRUNCATE TABLE mv_product_demand_analytics;

                -- (Same INSERT logic as incremental, but without dirty table filter)
                INSERT INTO mv_product_demand_analytics (
                    tenant_id, product_id, avg_daily_demand_30d, avg_daily_demand_90d, avg_daily_demand_365d,
                    demand_std_dev_30d, demand_std_dev_90d, max_daily_demand_30d, stockout_days_30d, zero_demand_days_30d,
                    demand_trend_slope, seasonality_index, coefficient_of_variation, days_since_last_sale, sales_velocity_ratio,
                    current_stock_level, days_of_inventory_on_hand, inventory_turnover_ratio, reorder_point, economic_order_quantity,
                    lead_time_days, minimum_order_quantity, is_perishable, shelf_life_days, created_at, updated_at
                )
                SELECT
                    sm.tenant_id, sm.product_id,
                    AVG(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '30 days' AND sm.movement_type = 'sales'),
                    AVG(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '90 days' AND sm.movement_type = 'sales'),
                    AVG(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '365 days' AND sm.movement_type = 'sales'),
                    STDDEV(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '30 days' AND sm.movement_type = 'sales'),
                    STDDEV(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '90 days' AND sm.movement_type = 'sales'),
                    MAX(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '30 days' AND sm.movement_type = 'sales'),
                    COUNT(*) FILTER (WHERE sm.current_stock_level = 0 AND sm.movement_date >= CURRENT_DATE - INTERVAL '30 days'),
                    COUNT(*) FILTER (WHERE ABS(sm.quantity) = 0 AND sm.movement_date >= CURRENT_DATE - INTERVAL '30 days'),
                    COALESCE(REGR_SLOPE(ABS(sm.quantity), EXTRACT(EPOCH FROM sm.movement_date)) 
                        FILTER (WHERE sm.movement_type = 'sales' AND sm.movement_date >= CURRENT_DATE - INTERVAL '90 days'), 0.0),
                    CASE WHEN AVG(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '365 days' AND sm.movement_type = 'sales') > 0 THEN
                        AVG(ABS(sm.quantity)) FILTER (WHERE EXTRACT(MONTH FROM sm.movement_date) = EXTRACT(MONTH FROM CURRENT_DATE) AND sm.movement_type = 'sales') /
                        AVG(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '365 days' AND sm.movement_type = 'sales')
                    ELSE 1.00 END,
                    CASE WHEN AVG(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '90 days' AND sm.movement_type = 'sales') > 0 THEN
                        STDDEV(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '90 days' AND sm.movement_type = 'sales') /
                        AVG(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '90 days' AND sm.movement_type = 'sales')
                    ELSE 0.00 END,
                    EXTRACT(DAY FROM (CURRENT_DATE - MAX(sm.movement_date) FILTER (WHERE sm.movement_type = 'sales'))),
                    CASE WHEN AVG(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '90 days' AND sm.movement_type = 'sales') > 0 THEN
                        AVG(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '30 days' AND sm.movement_type = 'sales') /
                        AVG(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '90 days' AND sm.movement_type = 'sales')
                    ELSE 0.00 END,
                    COALESCE(MAX(sm.current_stock_level), 0.00),
                    CASE WHEN AVG(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '30 days' AND sm.movement_type = 'sales') > 0 THEN
                        COALESCE(MAX(sm.current_stock_level), 0) / AVG(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '30 days' AND sm.movement_type = 'sales')
                    ELSE 0.0 END,
                    CASE WHEN AVG(sm.current_stock_level) > 0 THEN
                        SUM(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '365 days' AND sm.movement_type = 'sales') / AVG(sm.current_stock_level)
                    ELSE 0.00 END,
                    (AVG(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '30 days' AND sm.movement_type = 'sales') * COALESCE(p.lead_time_days, 0)) +
                    (1.65 * STDDEV(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '30 days' AND sm.movement_type = 'sales') * SQRT(COALESCE(p.lead_time_days, 0))),
                    SQRT((2 * SUM(ABS(sm.quantity)) FILTER (WHERE sm.movement_date >= CURRENT_DATE - INTERVAL '365 days' AND sm.movement_type = 'sales') * COALESCE(p.ordering_cost, 50)) / COALESCE(p.holding_cost_pct, 0.25)),
                    COALESCE(p.lead_time_days, 0),
                    COALESCE(p.minimum_order_quantity, 0.00),
                    COALESCE(p.is_perishable, false),
                    p.shelf_life_days,
                    CURRENT_TIMESTAMP,
                    CURRENT_TIMESTAMP
                FROM stock_movements sm
                JOIN products p ON p.id = sm.product_id
                GROUP BY sm.tenant_id, sm.product_id, p.lead_time_days, p.minimum_order_quantity, 
                         p.is_perishable, p.shelf_life_days, p.ordering_cost, p.holding_cost_pct;
            END;
            $$
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop triggers
        DB::statement('DROP TRIGGER IF EXISTS trg_sales_order_lines_mark_dirty ON sales_order_lines');
        DB::statement('DROP TRIGGER IF EXISTS trg_stock_movements_mark_dirty ON stock_movements');

        // Drop functions
        DB::statement('DROP FUNCTION IF EXISTS mark_product_demand_dirty_from_sales()');
        DB::statement('DROP FUNCTION IF EXISTS mark_product_demand_analytics_dirty()');

        // Drop procedures
        DB::statement('DROP PROCEDURE IF EXISTS refresh_product_demand_analytics_full()');
        DB::statement('DROP PROCEDURE IF EXISTS refresh_product_demand_analytics_incremental()');

        // Drop tables (cascades to partitions)
        DB::statement('DROP TABLE IF EXISTS mv_product_demand_analytics_dirty');
        DB::statement('DROP TABLE IF EXISTS mv_product_demand_analytics');
    }
};
