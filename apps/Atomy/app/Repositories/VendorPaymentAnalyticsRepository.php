<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Nexus\Payable\Contracts\VendorPaymentAnalyticsRepositoryInterface;

/**
 * Eloquent + Raw SQL implementation of VendorPaymentAnalyticsRepositoryInterface.
 * 
 * Queries the mv_vendor_payment_analytics materialized view for DuplicatePaymentDetectionExtractor.
 * All queries are tenant-scoped for security and partition pruning optimization.
 */
final readonly class VendorPaymentAnalyticsRepository implements VendorPaymentAnalyticsRepositoryInterface
{
    public function __construct(
        private string $tenantId
    ) {}

    public function getAveragePaymentAmount(string $vendorId): float
    {
        $result = DB::selectOne(
            "SELECT avg_payment_amount 
             FROM mv_vendor_payment_analytics 
             WHERE tenant_id = ? AND vendor_id = ?",
            [$this->tenantId, $vendorId]
        );

        return $result?->avg_payment_amount ?? 0.0;
    }

    public function getPaymentFrequencyScore(string $vendorId): float
    {
        $result = DB::selectOne(
            "SELECT payment_frequency_score 
             FROM mv_vendor_payment_analytics 
             WHERE tenant_id = ? AND vendor_id = ?",
            [$this->tenantId, $vendorId]
        );

        return $result?->payment_frequency_score ?? 0.0;
    }

    public function getPreferredPaymentMethod(string $vendorId): ?string
    {
        $result = DB::selectOne(
            "SELECT preferred_payment_method 
             FROM mv_vendor_payment_analytics 
             WHERE tenant_id = ? AND vendor_id = ?",
            [$this->tenantId, $vendorId]
        );

        return $result?->preferred_payment_method;
    }

    public function getLastPaymentDate(string $vendorId): ?\DateTimeImmutable
    {
        $result = DB::selectOne(
            "SELECT last_payment_date 
             FROM mv_vendor_payment_analytics 
             WHERE tenant_id = ? AND vendor_id = ?",
            [$this->tenantId, $vendorId]
        );

        if (!$result?->last_payment_date) {
            return null;
        }

        return new \DateTimeImmutable($result->last_payment_date);
    }

    public function getDuplicatePaymentCount90d(string $vendorId): int
    {
        $result = DB::selectOne(
            "SELECT duplicate_payment_count_90d 
             FROM mv_vendor_payment_analytics 
             WHERE tenant_id = ? AND vendor_id = ?",
            [$this->tenantId, $vendorId]
        );

        return $result?->duplicate_payment_count_90d ?? 0;
    }

    public function getAfterHoursPaymentCount90d(string $vendorId): int
    {
        $result = DB::selectOne(
            "SELECT after_hours_payment_count_90d 
             FROM mv_vendor_payment_analytics 
             WHERE tenant_id = ? AND vendor_id = ?",
            [$this->tenantId, $vendorId]
        );

        return $result?->after_hours_payment_count_90d ?? 0;
    }

    public function getRushPaymentCount90d(string $vendorId): int
    {
        $result = DB::selectOne(
            "SELECT rush_payment_count_90d 
             FROM mv_vendor_payment_analytics 
             WHERE tenant_id = ? AND vendor_id = ?",
            [$this->tenantId, $vendorId]
        );

        return $result?->rush_payment_count_90d ?? 0;
    }

    public function getSupplierDiversityScore(string $vendorId): float
    {
        $result = DB::selectOne(
            "SELECT supplier_diversity_score 
             FROM mv_vendor_payment_analytics 
             WHERE tenant_id = ? AND vendor_id = ?",
            [$this->tenantId, $vendorId]
        );

        return $result?->supplier_diversity_score ?? 0.0;
    }

    public function getAverageDaysToPayment(string $vendorId): float
    {
        $result = DB::selectOne(
            "SELECT avg_days_to_payment 
             FROM mv_vendor_payment_analytics 
             WHERE tenant_id = ? AND vendor_id = ?",
            [$this->tenantId, $vendorId]
        );

        return $result?->avg_days_to_payment ?? 0.0;
    }

    public function getPaymentAmountVolatility(string $vendorId): float
    {
        $result = DB::selectOne(
            "SELECT payment_amount_volatility 
             FROM mv_vendor_payment_analytics 
             WHERE tenant_id = ? AND vendor_id = ?",
            [$this->tenantId, $vendorId]
        );

        return $result?->payment_amount_volatility ?? 0.0;
    }

    public function findSimilarRecentPayments(
        string $vendorId,
        float $amount,
        \DateTimeImmutable $billDate,
        int $daysWindow = 7
    ): array {
        $startDate = $billDate->modify("-{$daysWindow} days")->format('Y-m-d');
        $endDate = $billDate->modify("+{$daysWindow} days")->format('Y-m-d');
        $minAmount = $amount - 0.01;
        $maxAmount = $amount + 0.01;

        $results = DB::select(
            "SELECT 
                id,
                bill_number,
                bill_date,
                total_amount,
                payment_method,
                description,
                created_at,
                ABS(total_amount - ?) AS amount_diff,
                EXTRACT(DAY FROM (bill_date - ?::date)) AS days_diff
             FROM vendor_bills
             WHERE tenant_id = ?
               AND vendor_id = ?
               AND bill_date BETWEEN ?::date AND ?::date
               AND total_amount BETWEEN ? AND ?
               AND status != 'void'
             ORDER BY amount_diff ASC, ABS(days_diff) ASC
             LIMIT 10",
            [
                $amount,
                $billDate->format('Y-m-d'),
                $this->tenantId,
                $vendorId,
                $startDate,
                $endDate,
                $minAmount,
                $maxAmount,
            ]
        );

        return array_map(function ($row) {
            return [
                'id' => $row->id,
                'bill_number' => $row->bill_number,
                'bill_date' => new \DateTimeImmutable($row->bill_date),
                'total_amount' => (float) $row->total_amount,
                'payment_method' => $row->payment_method,
                'description' => $row->description,
                'created_at' => new \DateTimeImmutable($row->created_at),
                'amount_diff' => (float) $row->amount_diff,
                'days_diff' => (int) $row->days_diff,
            ];
        }, $results);
    }

    /**
     * Refresh analytics for a specific vendor (called after bill posting).
     * 
     * This marks the vendor as dirty, triggering incremental refresh on next scheduled run.
     */
    public function refreshVendorAnalytics(string $vendorId): void
    {
        DB::insert(
            "INSERT INTO mv_vendor_payment_analytics_dirty (tenant_id, vendor_id, marked_at)
             VALUES (?, ?, CURRENT_TIMESTAMP)
             ON CONFLICT (tenant_id, vendor_id) DO UPDATE SET marked_at = CURRENT_TIMESTAMP",
            [$this->tenantId, $vendorId]
        );
    }
}
