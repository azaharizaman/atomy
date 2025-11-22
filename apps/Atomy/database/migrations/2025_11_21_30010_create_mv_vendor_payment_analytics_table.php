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
     * Creates the partitioned materialized view for vendor payment analytics.
     * Used by DuplicatePaymentDetectionExtractor for fraud prevention.
     *
     * Architecture:
     * - List partitioned by tenant_id (auto-provisioned via TenantCreatedEvent listener)
     * - Incremental refresh every 15 minutes via dirty_records tracking
     * - UNLOGGED dirty table for write performance
     */
    public function up(): void
    {
        // Create partitioned materialized view parent table
        DB::statement("
            CREATE TABLE mv_vendor_payment_analytics (
                tenant_id VARCHAR(26) NOT NULL,
                vendor_id VARCHAR(26) NOT NULL,
                avg_payment_amount DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
                payment_frequency_score DECIMAL(3, 2) NOT NULL DEFAULT 0.00,
                preferred_payment_method VARCHAR(50),
                last_payment_date DATE,
                duplicate_payment_count_90d INTEGER NOT NULL DEFAULT 0,
                after_hours_payment_count_90d INTEGER NOT NULL DEFAULT 0,
                rush_payment_count_90d INTEGER NOT NULL DEFAULT 0,
                supplier_diversity_score DECIMAL(3, 2) NOT NULL DEFAULT 0.00,
                avg_days_to_payment DECIMAL(5, 1) NOT NULL DEFAULT 0.0,
                payment_amount_volatility DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (tenant_id, vendor_id)
            ) PARTITION BY LIST (tenant_id)
        ");

        // Create dirty records tracking table (UNLOGGED for performance)
        DB::statement("
            CREATE UNLOGGED TABLE mv_vendor_payment_analytics_dirty (
                tenant_id VARCHAR(26) NOT NULL,
                vendor_id VARCHAR(26) NOT NULL,
                marked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (tenant_id, vendor_id)
            )
        ");

        // Create trigger function to mark dirty records on vendor_bills changes
        DB::statement("
            CREATE OR REPLACE FUNCTION mark_vendor_payment_analytics_dirty()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO mv_vendor_payment_analytics_dirty (tenant_id, vendor_id)
                VALUES (COALESCE(NEW.tenant_id, OLD.tenant_id), COALESCE(NEW.vendor_id, OLD.vendor_id))
                ON CONFLICT (tenant_id, vendor_id) DO UPDATE SET marked_at = CURRENT_TIMESTAMP;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Attach trigger to vendor_bills table
        DB::statement("
            CREATE TRIGGER trg_vendor_bills_mark_dirty
            AFTER INSERT OR UPDATE OR DELETE ON vendor_bills
            FOR EACH ROW
            EXECUTE FUNCTION mark_vendor_payment_analytics_dirty()
        ");

        // Create trigger function to mark dirty on payment_applications changes
        DB::statement("
            CREATE OR REPLACE FUNCTION mark_vendor_payment_dirty_from_applications()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO mv_vendor_payment_analytics_dirty (tenant_id, vendor_id)
                SELECT vb.tenant_id, vb.vendor_id
                FROM vendor_bills vb
                WHERE vb.id = COALESCE(NEW.bill_id, OLD.bill_id)
                ON CONFLICT (tenant_id, vendor_id) DO UPDATE SET marked_at = CURRENT_TIMESTAMP;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Attach trigger to payment_applications table
        DB::statement("
            CREATE TRIGGER trg_payment_applications_mark_dirty
            AFTER INSERT OR UPDATE OR DELETE ON payment_applications
            FOR EACH ROW
            EXECUTE FUNCTION mark_vendor_payment_dirty_from_applications()
        ");

        // Create stored procedure for incremental refresh (called every 15 minutes)
        DB::statement("
            CREATE OR REPLACE PROCEDURE refresh_vendor_payment_analytics_incremental()
            LANGUAGE plpgsql
            AS $$
            BEGIN
                -- Update existing partitions for dirty records only
                INSERT INTO mv_vendor_payment_analytics (
                    tenant_id,
                    vendor_id,
                    avg_payment_amount,
                    payment_frequency_score,
                    preferred_payment_method,
                    last_payment_date,
                    duplicate_payment_count_90d,
                    after_hours_payment_count_90d,
                    rush_payment_count_90d,
                    supplier_diversity_score,
                    avg_days_to_payment,
                    payment_amount_volatility,
                    updated_at
                )
                SELECT
                    vb.tenant_id,
                    vb.vendor_id,
                    AVG(vb.total_amount) AS avg_payment_amount,
                    CASE 
                        WHEN COUNT(DISTINCT DATE_TRUNC('month', vb.bill_date)) = 0 THEN 0.00
                        ELSE LEAST(1.00, COUNT(*) / (COUNT(DISTINCT DATE_TRUNC('month', vb.bill_date)) * 4.0))
                    END AS payment_frequency_score,
                    MODE() WITHIN GROUP (ORDER BY vb.payment_method) AS preferred_payment_method,
                    MAX(vb.bill_date) AS last_payment_date,
                    COUNT(*) FILTER (WHERE EXISTS (
                        SELECT 1 FROM vendor_bills vb2
                        WHERE vb2.vendor_id = vb.vendor_id
                            AND vb2.id != vb.id
                            AND ABS(vb2.total_amount - vb.total_amount) < 0.01
                            AND vb2.bill_date BETWEEN vb.bill_date - INTERVAL '7 days' AND vb.bill_date + INTERVAL '7 days'
                    )) AS duplicate_payment_count_90d,
                    COUNT(*) FILTER (WHERE EXTRACT(HOUR FROM vb.created_at) NOT BETWEEN 8 AND 17) AS after_hours_payment_count_90d,
                    COUNT(*) FILTER (WHERE vb.due_date < vb.bill_date + INTERVAL '3 days') AS rush_payment_count_90d,
                    CASE 
                        WHEN COUNT(DISTINCT vb.vendor_id) = 0 THEN 0.00
                        ELSE LEAST(1.00, COUNT(DISTINCT vb.vendor_id) / 10.0)
                    END AS supplier_diversity_score,
                    AVG(EXTRACT(DAY FROM (vb.paid_date - vb.bill_date))) AS avg_days_to_payment,
                    STDDEV(vb.total_amount) AS payment_amount_volatility,
                    CURRENT_TIMESTAMP
                FROM vendor_bills vb
                WHERE vb.bill_date >= CURRENT_DATE - INTERVAL '90 days'
                    AND EXISTS (
                        SELECT 1 FROM mv_vendor_payment_analytics_dirty d
                        WHERE d.tenant_id = vb.tenant_id AND d.vendor_id = vb.vendor_id
                    )
                GROUP BY vb.tenant_id, vb.vendor_id
                ON CONFLICT (tenant_id, vendor_id) DO UPDATE SET
                    avg_payment_amount = EXCLUDED.avg_payment_amount,
                    payment_frequency_score = EXCLUDED.payment_frequency_score,
                    preferred_payment_method = EXCLUDED.preferred_payment_method,
                    last_payment_date = EXCLUDED.last_payment_date,
                    duplicate_payment_count_90d = EXCLUDED.duplicate_payment_count_90d,
                    after_hours_payment_count_90d = EXCLUDED.after_hours_payment_count_90d,
                    rush_payment_count_90d = EXCLUDED.rush_payment_count_90d,
                    supplier_diversity_score = EXCLUDED.supplier_diversity_score,
                    avg_days_to_payment = EXCLUDED.avg_days_to_payment,
                    payment_amount_volatility = EXCLUDED.payment_amount_volatility,
                    updated_at = EXCLUDED.updated_at;

                -- Clear dirty records
                DELETE FROM mv_vendor_payment_analytics_dirty;
            END;
            $$
        ");

        // Create stored procedure for full refresh (hourly fallback)
        DB::statement("
            CREATE OR REPLACE PROCEDURE refresh_vendor_payment_analytics_full()
            LANGUAGE plpgsql
            AS $$
            BEGIN
                -- Truncate all partitions and rebuild from scratch
                TRUNCATE TABLE mv_vendor_payment_analytics;

                INSERT INTO mv_vendor_payment_analytics (
                    tenant_id,
                    vendor_id,
                    avg_payment_amount,
                    payment_frequency_score,
                    preferred_payment_method,
                    last_payment_date,
                    duplicate_payment_count_90d,
                    after_hours_payment_count_90d,
                    rush_payment_count_90d,
                    supplier_diversity_score,
                    avg_days_to_payment,
                    payment_amount_volatility,
                    created_at,
                    updated_at
                )
                SELECT
                    vb.tenant_id,
                    vb.vendor_id,
                    AVG(vb.total_amount),
                    CASE 
                        WHEN COUNT(DISTINCT DATE_TRUNC('month', vb.bill_date)) = 0 THEN 0.00
                        ELSE LEAST(1.00, COUNT(*) / (COUNT(DISTINCT DATE_TRUNC('month', vb.bill_date)) * 4.0))
                    END,
                    MODE() WITHIN GROUP (ORDER BY vb.payment_method),
                    MAX(vb.bill_date),
                    COUNT(*) FILTER (WHERE EXISTS (
                        SELECT 1 FROM vendor_bills vb2
                        WHERE vb2.vendor_id = vb.vendor_id
                            AND vb2.id != vb.id
                            AND ABS(vb2.total_amount - vb.total_amount) < 0.01
                            AND vb2.bill_date BETWEEN vb.bill_date - INTERVAL '7 days' AND vb.bill_date + INTERVAL '7 days'
                    )),
                    COUNT(*) FILTER (WHERE EXTRACT(HOUR FROM vb.created_at) NOT BETWEEN 8 AND 17),
                    COUNT(*) FILTER (WHERE vb.due_date < vb.bill_date + INTERVAL '3 days'),
                    CASE 
                        WHEN COUNT(DISTINCT vb.vendor_id) = 0 THEN 0.00
                        ELSE LEAST(1.00, COUNT(DISTINCT vb.vendor_id) / 10.0)
                    END,
                    AVG(EXTRACT(DAY FROM (vb.paid_date - vb.bill_date))),
                    STDDEV(vb.total_amount),
                    CURRENT_TIMESTAMP,
                    CURRENT_TIMESTAMP
                FROM vendor_bills vb
                WHERE vb.bill_date >= CURRENT_DATE - INTERVAL '90 days'
                GROUP BY vb.tenant_id, vb.vendor_id;
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
        DB::statement('DROP TRIGGER IF EXISTS trg_payment_applications_mark_dirty ON payment_applications');
        DB::statement('DROP TRIGGER IF EXISTS trg_vendor_bills_mark_dirty ON vendor_bills');

        // Drop functions
        DB::statement('DROP FUNCTION IF EXISTS mark_vendor_payment_dirty_from_applications()');
        DB::statement('DROP FUNCTION IF EXISTS mark_vendor_payment_analytics_dirty()');

        // Drop procedures
        DB::statement('DROP PROCEDURE IF EXISTS refresh_vendor_payment_analytics_full()');
        DB::statement('DROP PROCEDURE IF EXISTS refresh_vendor_payment_analytics_incremental()');

        // Drop tables (cascades to partitions)
        DB::statement('DROP TABLE IF EXISTS mv_vendor_payment_analytics_dirty');
        DB::statement('DROP TABLE IF EXISTS mv_vendor_payment_analytics');
    }
};
