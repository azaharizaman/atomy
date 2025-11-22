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
     * Creates the partitioned materialized view for customer payment analytics.
     * Used by CustomerPaymentPredictionExtractor for cash flow forecasting.
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
            CREATE TABLE mv_customer_payment_analytics (
                tenant_id VARCHAR(26) NOT NULL,
                customer_id VARCHAR(26) NOT NULL,
                avg_payment_delay_days DECIMAL(5, 1) NOT NULL DEFAULT 0.0,
                payment_delay_std_dev DECIMAL(5, 1) NOT NULL DEFAULT 0.0,
                on_time_payment_rate DECIMAL(3, 2) NOT NULL DEFAULT 0.00,
                late_payment_rate DECIMAL(3, 2) NOT NULL DEFAULT 0.00,
                avg_days_to_pay DECIMAL(5, 1) NOT NULL DEFAULT 0.0,
                payment_frequency_score DECIMAL(3, 2) NOT NULL DEFAULT 0.00,
                has_payment_plan_active BOOLEAN NOT NULL DEFAULT false,
                payment_method_consistency_score DECIMAL(3, 2) NOT NULL DEFAULT 0.00,
                current_credit_limit DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
                credit_utilization_ratio DECIMAL(3, 2) NOT NULL DEFAULT 0.00,
                credit_limit_exceeded_count INTEGER NOT NULL DEFAULT 0,
                overdue_balance DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
                highest_overdue_days INTEGER NOT NULL DEFAULT 0,
                customer_tenure_days INTEGER NOT NULL DEFAULT 0,
                total_lifetime_value DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
                avg_invoice_amount DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
                invoice_count_12m INTEGER NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (tenant_id, customer_id)
            ) PARTITION BY LIST (tenant_id)
        ");

        // Create dirty records tracking table (UNLOGGED for performance)
        DB::statement("
            CREATE UNLOGGED TABLE mv_customer_payment_analytics_dirty (
                tenant_id VARCHAR(26) NOT NULL,
                customer_id VARCHAR(26) NOT NULL,
                marked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (tenant_id, customer_id)
            )
        ");

        // Create trigger function to mark dirty records on customer_invoices changes
        DB::statement("
            CREATE OR REPLACE FUNCTION mark_customer_payment_analytics_dirty()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO mv_customer_payment_analytics_dirty (tenant_id, customer_id)
                VALUES (COALESCE(NEW.tenant_id, OLD.tenant_id), COALESCE(NEW.customer_id, OLD.customer_id))
                ON CONFLICT (tenant_id, customer_id) DO UPDATE SET marked_at = CURRENT_TIMESTAMP;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Attach trigger to customer_invoices table
        DB::statement("
            CREATE TRIGGER trg_customer_invoices_mark_dirty
            AFTER INSERT OR UPDATE OR DELETE ON customer_invoices
            FOR EACH ROW
            EXECUTE FUNCTION mark_customer_payment_analytics_dirty()
        ");

        // Create trigger function to mark dirty on payment_receipts changes
        DB::statement("
            CREATE OR REPLACE FUNCTION mark_customer_payment_dirty_from_receipts()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO mv_customer_payment_analytics_dirty (tenant_id, customer_id)
                SELECT ci.tenant_id, ci.customer_id
                FROM customer_invoices ci
                JOIN payment_allocations pa ON pa.invoice_id = ci.id
                WHERE pa.receipt_id = COALESCE(NEW.id, OLD.id)
                ON CONFLICT (tenant_id, customer_id) DO UPDATE SET marked_at = CURRENT_TIMESTAMP;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Attach trigger to payment_receipts table
        DB::statement("
            CREATE TRIGGER trg_payment_receipts_mark_dirty
            AFTER INSERT OR UPDATE OR DELETE ON payment_receipts
            FOR EACH ROW
            EXECUTE FUNCTION mark_customer_payment_dirty_from_receipts()
        ");

        // Create stored procedure for incremental refresh (called every 15 minutes)
        DB::statement("
            CREATE OR REPLACE PROCEDURE refresh_customer_payment_analytics_incremental()
            LANGUAGE plpgsql
            AS $$
            BEGIN
                -- Update existing partitions for dirty records only
                INSERT INTO mv_customer_payment_analytics (
                    tenant_id,
                    customer_id,
                    avg_payment_delay_days,
                    payment_delay_std_dev,
                    on_time_payment_rate,
                    late_payment_rate,
                    avg_days_to_pay,
                    payment_frequency_score,
                    has_payment_plan_active,
                    payment_method_consistency_score,
                    current_credit_limit,
                    credit_utilization_ratio,
                    credit_limit_exceeded_count,
                    overdue_balance,
                    highest_overdue_days,
                    customer_tenure_days,
                    total_lifetime_value,
                    avg_invoice_amount,
                    invoice_count_12m,
                    updated_at
                )
                SELECT
                    ci.tenant_id,
                    ci.customer_id,
                    AVG(EXTRACT(DAY FROM (pr.receipt_date - ci.due_date))) AS avg_payment_delay_days,
                    STDDEV(EXTRACT(DAY FROM (pr.receipt_date - ci.due_date))) AS payment_delay_std_dev,
                    COUNT(*) FILTER (WHERE pr.receipt_date <= ci.due_date)::DECIMAL / NULLIF(COUNT(*), 0) AS on_time_payment_rate,
                    COUNT(*) FILTER (WHERE pr.receipt_date > ci.due_date + INTERVAL '7 days')::DECIMAL / NULLIF(COUNT(*), 0) AS late_payment_rate,
                    AVG(EXTRACT(DAY FROM (pr.receipt_date - ci.invoice_date))) AS avg_days_to_pay,
                    CASE 
                        WHEN COUNT(DISTINCT DATE_TRUNC('month', pr.receipt_date)) = 0 THEN 0.00
                        ELSE LEAST(1.00, COUNT(*)::DECIMAL / (COUNT(DISTINCT DATE_TRUNC('month', pr.receipt_date)) * 4.0))
                    END AS payment_frequency_score,
                    EXISTS (SELECT 1 FROM payment_plans pp WHERE pp.customer_id = ci.customer_id AND pp.status = 'active') AS has_payment_plan_active,
                    CASE 
                        WHEN COUNT(*) = 0 THEN 0.00
                        ELSE 1.0 - (COUNT(DISTINCT pr.payment_method)::DECIMAL / COUNT(*)::DECIMAL)
                    END AS payment_method_consistency_score,
                    COALESCE(c.credit_limit, 0.00) AS current_credit_limit,
                    CASE 
                        WHEN COALESCE(c.credit_limit, 0) = 0 THEN 0.00
                        ELSE LEAST(1.00, SUM(ci.total_amount) / c.credit_limit)
                    END AS credit_utilization_ratio,
                    COUNT(*) FILTER (WHERE ci.total_amount > COALESCE(c.credit_limit, 0)) AS credit_limit_exceeded_count,
                    SUM(ci.total_amount) FILTER (WHERE ci.due_date < CURRENT_DATE AND ci.status != 'paid') AS overdue_balance,
                    MAX(EXTRACT(DAY FROM (CURRENT_DATE - ci.due_date))) FILTER (WHERE ci.status != 'paid') AS highest_overdue_days,
                    EXTRACT(DAY FROM (CURRENT_DATE - MIN(ci.invoice_date))) AS customer_tenure_days,
                    SUM(ci.total_amount) AS total_lifetime_value,
                    AVG(ci.total_amount) FILTER (WHERE ci.invoice_date >= CURRENT_DATE - INTERVAL '12 months') AS avg_invoice_amount,
                    COUNT(*) FILTER (WHERE ci.invoice_date >= CURRENT_DATE - INTERVAL '12 months') AS invoice_count_12m,
                    CURRENT_TIMESTAMP
                FROM customer_invoices ci
                LEFT JOIN payment_receipts pr ON pr.id IN (
                    SELECT pa.receipt_id FROM payment_allocations pa WHERE pa.invoice_id = ci.id
                )
                LEFT JOIN customers c ON c.id = ci.customer_id
                WHERE EXISTS (
                    SELECT 1 FROM mv_customer_payment_analytics_dirty d
                    WHERE d.tenant_id = ci.tenant_id AND d.customer_id = ci.customer_id
                )
                GROUP BY ci.tenant_id, ci.customer_id, c.credit_limit
                ON CONFLICT (tenant_id, customer_id) DO UPDATE SET
                    avg_payment_delay_days = EXCLUDED.avg_payment_delay_days,
                    payment_delay_std_dev = EXCLUDED.payment_delay_std_dev,
                    on_time_payment_rate = EXCLUDED.on_time_payment_rate,
                    late_payment_rate = EXCLUDED.late_payment_rate,
                    avg_days_to_pay = EXCLUDED.avg_days_to_pay,
                    payment_frequency_score = EXCLUDED.payment_frequency_score,
                    has_payment_plan_active = EXCLUDED.has_payment_plan_active,
                    payment_method_consistency_score = EXCLUDED.payment_method_consistency_score,
                    current_credit_limit = EXCLUDED.current_credit_limit,
                    credit_utilization_ratio = EXCLUDED.credit_utilization_ratio,
                    credit_limit_exceeded_count = EXCLUDED.credit_limit_exceeded_count,
                    overdue_balance = EXCLUDED.overdue_balance,
                    highest_overdue_days = EXCLUDED.highest_overdue_days,
                    customer_tenure_days = EXCLUDED.customer_tenure_days,
                    total_lifetime_value = EXCLUDED.total_lifetime_value,
                    avg_invoice_amount = EXCLUDED.avg_invoice_amount,
                    invoice_count_12m = EXCLUDED.invoice_count_12m,
                    updated_at = EXCLUDED.updated_at;

                -- Clear dirty records
                DELETE FROM mv_customer_payment_analytics_dirty;
            END;
            $$
        ");

        // Create stored procedure for full refresh (hourly fallback)
        DB::statement("
            CREATE OR REPLACE PROCEDURE refresh_customer_payment_analytics_full()
            LANGUAGE plpgsql
            AS $$
            BEGIN
                -- Truncate all partitions and rebuild from scratch
                TRUNCATE TABLE mv_customer_payment_analytics;

                INSERT INTO mv_customer_payment_analytics (
                    tenant_id,
                    customer_id,
                    avg_payment_delay_days,
                    payment_delay_std_dev,
                    on_time_payment_rate,
                    late_payment_rate,
                    avg_days_to_pay,
                    payment_frequency_score,
                    has_payment_plan_active,
                    payment_method_consistency_score,
                    current_credit_limit,
                    credit_utilization_ratio,
                    credit_limit_exceeded_count,
                    overdue_balance,
                    highest_overdue_days,
                    customer_tenure_days,
                    total_lifetime_value,
                    avg_invoice_amount,
                    invoice_count_12m,
                    created_at,
                    updated_at
                )
                SELECT
                    ci.tenant_id,
                    ci.customer_id,
                    AVG(EXTRACT(DAY FROM (pr.receipt_date - ci.due_date))),
                    STDDEV(EXTRACT(DAY FROM (pr.receipt_date - ci.due_date))),
                    COUNT(*) FILTER (WHERE pr.receipt_date <= ci.due_date)::DECIMAL / NULLIF(COUNT(*), 0),
                    COUNT(*) FILTER (WHERE pr.receipt_date > ci.due_date + INTERVAL '7 days')::DECIMAL / NULLIF(COUNT(*), 0),
                    AVG(EXTRACT(DAY FROM (pr.receipt_date - ci.invoice_date))),
                    CASE 
                        WHEN COUNT(DISTINCT DATE_TRUNC('month', pr.receipt_date)) = 0 THEN 0.00
                        ELSE LEAST(1.00, COUNT(*)::DECIMAL / (COUNT(DISTINCT DATE_TRUNC('month', pr.receipt_date)) * 4.0))
                    END,
                    EXISTS (SELECT 1 FROM payment_plans pp WHERE pp.customer_id = ci.customer_id AND pp.status = 'active'),
                    CASE 
                        WHEN COUNT(*) = 0 THEN 0.00
                        ELSE 1.0 - (COUNT(DISTINCT pr.payment_method)::DECIMAL / COUNT(*)::DECIMAL)
                    END,
                    COALESCE(c.credit_limit, 0.00),
                    CASE 
                        WHEN COALESCE(c.credit_limit, 0) = 0 THEN 0.00
                        ELSE LEAST(1.00, SUM(ci.total_amount) / c.credit_limit)
                    END,
                    COUNT(*) FILTER (WHERE ci.total_amount > COALESCE(c.credit_limit, 0)),
                    SUM(ci.total_amount) FILTER (WHERE ci.due_date < CURRENT_DATE AND ci.status != 'paid'),
                    MAX(EXTRACT(DAY FROM (CURRENT_DATE - ci.due_date))) FILTER (WHERE ci.status != 'paid'),
                    EXTRACT(DAY FROM (CURRENT_DATE - MIN(ci.invoice_date))),
                    SUM(ci.total_amount),
                    AVG(ci.total_amount) FILTER (WHERE ci.invoice_date >= CURRENT_DATE - INTERVAL '12 months'),
                    COUNT(*) FILTER (WHERE ci.invoice_date >= CURRENT_DATE - INTERVAL '12 months'),
                    CURRENT_TIMESTAMP,
                    CURRENT_TIMESTAMP
                FROM customer_invoices ci
                LEFT JOIN payment_receipts pr ON pr.id IN (
                    SELECT pa.receipt_id FROM payment_allocations pa WHERE pa.invoice_id = ci.id
                )
                LEFT JOIN customers c ON c.id = ci.customer_id
                GROUP BY ci.tenant_id, ci.customer_id, c.credit_limit;
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
        DB::statement('DROP TRIGGER IF EXISTS trg_payment_receipts_mark_dirty ON payment_receipts');
        DB::statement('DROP TRIGGER IF EXISTS trg_customer_invoices_mark_dirty ON customer_invoices');

        // Drop functions
        DB::statement('DROP FUNCTION IF EXISTS mark_customer_payment_dirty_from_receipts()');
        DB::statement('DROP FUNCTION IF EXISTS mark_customer_payment_analytics_dirty()');

        // Drop procedures
        DB::statement('DROP PROCEDURE IF EXISTS refresh_customer_payment_analytics_full()');
        DB::statement('DROP PROCEDURE IF EXISTS refresh_customer_payment_analytics_incremental()');

        // Drop tables (cascades to partitions)
        DB::statement('DROP TABLE IF EXISTS mv_customer_payment_analytics_dirty');
        DB::statement('DROP TABLE IF EXISTS mv_customer_payment_analytics');
    }
};
