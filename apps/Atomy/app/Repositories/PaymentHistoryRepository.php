<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Nexus\Receivable\Contracts\PaymentHistoryRepositoryInterface;

/**
 * Eloquent + Raw SQL implementation of PaymentHistoryRepositoryInterface.
 * 
 * Queries the mv_customer_payment_analytics materialized view for CustomerPaymentPredictionExtractor.
 * All queries are tenant-scoped for security and partition pruning optimization.
 */
final readonly class PaymentHistoryRepository implements PaymentHistoryRepositoryInterface
{
    public function __construct(
        private string $tenantId
    ) {}

    public function getAveragePaymentDelayDays(string $customerId): float
    {
        $result = DB::selectOne(
            "SELECT avg_payment_delay_days 
             FROM mv_customer_payment_analytics 
             WHERE tenant_id = ? AND customer_id = ?",
            [$this->tenantId, $customerId]
        );

        return $result?->avg_payment_delay_days ?? 0.0;
    }

    public function getPaymentDelayStdDev(string $customerId): float
    {
        $result = DB::selectOne(
            "SELECT payment_delay_std_dev 
             FROM mv_customer_payment_analytics 
             WHERE tenant_id = ? AND customer_id = ?",
            [$this->tenantId, $customerId]
        );

        return $result?->payment_delay_std_dev ?? 0.0;
    }

    public function getOnTimePaymentRate(string $customerId): float
    {
        $result = DB::selectOne(
            "SELECT on_time_payment_rate 
             FROM mv_customer_payment_analytics 
             WHERE tenant_id = ? AND customer_id = ?",
            [$this->tenantId, $customerId]
        );

        return $result?->on_time_payment_rate ?? 0.0;
    }

    public function getLatePaymentRate(string $customerId): float
    {
        $result = DB::selectOne(
            "SELECT late_payment_rate 
             FROM mv_customer_payment_analytics 
             WHERE tenant_id = ? AND customer_id = ?",
            [$this->tenantId, $customerId]
        );

        return $result?->late_payment_rate ?? 0.0;
    }

    public function getAverageDaysToPay(string $customerId): float
    {
        $result = DB::selectOne(
            "SELECT avg_days_to_pay 
             FROM mv_customer_payment_analytics 
             WHERE tenant_id = ? AND customer_id = ?",
            [$this->tenantId, $customerId]
        );

        return $result?->avg_days_to_pay ?? 0.0;
    }

    public function getPaymentFrequencyScore(string $customerId): float
    {
        $result = DB::selectOne(
            "SELECT payment_frequency_score 
             FROM mv_customer_payment_analytics 
             WHERE tenant_id = ? AND customer_id = ?",
            [$this->tenantId, $customerId]
        );

        return $result?->payment_frequency_score ?? 0.0;
    }

    public function hasActivePaymentPlan(string $customerId): bool
    {
        $result = DB::selectOne(
            "SELECT has_payment_plan_active 
             FROM mv_customer_payment_analytics 
             WHERE tenant_id = ? AND customer_id = ?",
            [$this->tenantId, $customerId]
        );

        return $result?->has_payment_plan_active ?? false;
    }

    public function getPaymentMethodConsistencyScore(string $customerId): float
    {
        $result = DB::selectOne(
            "SELECT payment_method_consistency_score 
             FROM mv_customer_payment_analytics 
             WHERE tenant_id = ? AND customer_id = ?",
            [$this->tenantId, $customerId]
        );

        return $result?->payment_method_consistency_score ?? 0.0;
    }

    public function getCurrentCreditLimit(string $customerId): float
    {
        $result = DB::selectOne(
            "SELECT current_credit_limit 
             FROM mv_customer_payment_analytics 
             WHERE tenant_id = ? AND customer_id = ?",
            [$this->tenantId, $customerId]
        );

        return $result?->current_credit_limit ?? 0.0;
    }

    public function getCreditUtilizationRatio(string $customerId): float
    {
        $result = DB::selectOne(
            "SELECT credit_utilization_ratio 
             FROM mv_customer_payment_analytics 
             WHERE tenant_id = ? AND customer_id = ?",
            [$this->tenantId, $customerId]
        );

        return $result?->credit_utilization_ratio ?? 0.0;
    }

    public function getCreditLimitExceededCount(string $customerId): int
    {
        $result = DB::selectOne(
            "SELECT credit_limit_exceeded_count 
             FROM mv_customer_payment_analytics 
             WHERE tenant_id = ? AND customer_id = ?",
            [$this->tenantId, $customerId]
        );

        return $result?->credit_limit_exceeded_count ?? 0;
    }

    public function getOverdueBalance(string $customerId): float
    {
        $result = DB::selectOne(
            "SELECT overdue_balance 
             FROM mv_customer_payment_analytics 
             WHERE tenant_id = ? AND customer_id = ?",
            [$this->tenantId, $customerId]
        );

        return $result?->overdue_balance ?? 0.0;
    }

    public function getHighestOverdueDays(string $customerId): int
    {
        $result = DB::selectOne(
            "SELECT highest_overdue_days 
             FROM mv_customer_payment_analytics 
             WHERE tenant_id = ? AND customer_id = ?",
            [$this->tenantId, $customerId]
        );

        return $result?->highest_overdue_days ?? 0;
    }

    public function getCustomerTenureDays(string $customerId): int
    {
        $result = DB::selectOne(
            "SELECT customer_tenure_days 
             FROM mv_customer_payment_analytics 
             WHERE tenant_id = ? AND customer_id = ?",
            [$this->tenantId, $customerId]
        );

        return $result?->customer_tenure_days ?? 0;
    }

    public function getTotalLifetimeValue(string $customerId): float
    {
        $result = DB::selectOne(
            "SELECT total_lifetime_value 
             FROM mv_customer_payment_analytics 
             WHERE tenant_id = ? AND customer_id = ?",
            [$this->tenantId, $customerId]
        );

        return $result?->total_lifetime_value ?? 0.0;
    }

    public function getAverageInvoiceAmount(string $customerId): float
    {
        $result = DB::selectOne(
            "SELECT avg_invoice_amount 
             FROM mv_customer_payment_analytics 
             WHERE tenant_id = ? AND customer_id = ?",
            [$this->tenantId, $customerId]
        );

        return $result?->avg_invoice_amount ?? 0.0;
    }

    public function getInvoiceCount12m(string $customerId): int
    {
        $result = DB::selectOne(
            "SELECT invoice_count_12m 
             FROM mv_customer_payment_analytics 
             WHERE tenant_id = ? AND customer_id = ?",
            [$this->tenantId, $customerId]
        );

        return $result?->invoice_count_12m ?? 0;
    }

    /**
     * Refresh analytics for a specific customer (called after invoice posting or payment receipt).
     * 
     * This marks the customer as dirty, triggering incremental refresh on next scheduled run.
     */
    public function refreshCustomerAnalytics(string $customerId): void
    {
        DB::insert(
            "INSERT INTO mv_customer_payment_analytics_dirty (tenant_id, customer_id, marked_at)
             VALUES (?, ?, CURRENT_TIMESTAMP)
             ON CONFLICT (tenant_id, customer_id) DO UPDATE SET marked_at = CURRENT_TIMESTAMP",
            [$this->tenantId, $customerId]
        );
    }
}
