<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Budget;
use App\Models\BudgetTransaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Nexus\Budget\Contracts\BudgetAnalyticsRepositoryInterface;
use Nexus\Budget\Enums\TransactionType;
use Nexus\Budget\ValueObjects\BudgetConsolidation;
use Nexus\Budget\ValueObjects\ManagerPerformanceScore;
use Nexus\Currency\ValueObjects\Money;

/**
 * Database Budget Analytics Repository
 * 
 * Laravel/Eloquent implementation of BudgetAnalyticsRepositoryInterface.
 * Uses recursive CTEs for hierarchical consolidation and caching for performance.
 */
final class DbBudgetAnalyticsRepository implements BudgetAnalyticsRepositoryInterface
{
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private readonly Budget $budgetModel,
        private readonly BudgetTransaction $transactionModel
    ) {}

    public function getConsolidatedBudget(string $departmentId, string $periodId): BudgetConsolidation
    {
        $cacheKey = "budget.consolidated.{$departmentId}.{$periodId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($departmentId, $periodId) {
            // Get all budgets for department and descendants using recursive CTE
            $results = DB::select("
                WITH RECURSIVE dept_budgets AS (
                    SELECT b.* 
                    FROM budgets b
                    WHERE b.department_id = ?
                      AND b.period_id = ?
                      AND b.is_simulation = false
                      AND b.deleted_at IS NULL
                    
                    UNION ALL
                    
                    SELECT b.*
                    FROM budgets b
                    INNER JOIN dept_budgets db ON b.parent_budget_id = db.id
                    WHERE b.period_id = ?
                      AND b.is_simulation = false
                      AND b.deleted_at IS NULL
                )
                SELECT 
                    COUNT(*) as budget_count,
                    SUM(allocated_amount_functional) as total_allocated,
                    SUM(committed_amount) as total_committed,
                    SUM(actual_amount) as total_actual,
                    SUM(available_amount) as total_available,
                    functional_currency as currency,
                    SUM(CASE WHEN actual_amount > allocated_amount_functional THEN 1 ELSE 0 END) as over_budget_count
                FROM dept_budgets
                GROUP BY functional_currency
            ", [$departmentId, $periodId, $periodId]);

            if (empty($results)) {
                return new BudgetConsolidation(
                    departmentId: $departmentId,
                    periodId: $periodId,
                    budgetCount: 0,
                    totalAllocatedAmount: Money::of(0, 'MYR'),
                    totalCommittedAmount: Money::of(0, 'MYR'),
                    totalActualAmount: Money::of(0, 'MYR'),
                    totalAvailableAmount: Money::of(0, 'MYR'),
                    utilizationPercentage: 0.0,
                    overBudgetCount: 0
                );
            }

            $result = $results[0];
            $currency = $result->currency;

            $totalAllocated = (float) $result->total_allocated;
            $utilizationPct = $totalAllocated > 0 
                ? ((float) $result->total_actual / $totalAllocated) * 100 
                : 0.0;

            return new BudgetConsolidation(
                departmentId: $departmentId,
                periodId: $periodId,
                budgetCount: (int) $result->budget_count,
                totalAllocatedAmount: Money::of($totalAllocated, $currency),
                totalCommittedAmount: Money::of((float) $result->total_committed, $currency),
                totalActualAmount: Money::of((float) $result->total_actual, $currency),
                totalAvailableAmount: Money::of((float) $result->total_available, $currency),
                utilizationPercentage: $utilizationPct,
                overBudgetCount: (int) $result->over_budget_count
            );
        });
    }

    public function getBurnRateByDepartment(string $departmentId, int $periodDays): float
    {
        $cacheKey = "budget.burn_rate.{$departmentId}.{$periodDays}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($departmentId, $periodDays) {
            $result = DB::selectOne("
                SELECT 
                    SUM(bt.amount) / ? as daily_burn_rate
                FROM budget_transactions bt
                INNER JOIN budgets b ON bt.budget_id = b.id
                WHERE b.department_id = ?
                  AND bt.transaction_type = ?
                  AND bt.created_at >= NOW() - INTERVAL ? DAY
            ", [$periodDays, $departmentId, TransactionType::Actual->value, $periodDays]);

            return $result ? (float) $result->daily_burn_rate : 0.0;
        });
    }

    public function getManagerPerformanceScore(string $departmentId, int $periodsBack = 12): ManagerPerformanceScore
    {
        $cacheKey = "budget.manager_performance.{$departmentId}.{$periodsBack}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($departmentId, $periodsBack) {
            $result = DB::selectOne("
                SELECT 
                    COUNT(DISTINCT b.period_id) as periods_managed,
                    COUNT(*) as total_budgets,
                    SUM(CASE WHEN b.actual_amount <= b.allocated_amount_functional THEN 1 ELSE 0 END) as budgets_on_target,
                    AVG(ABS((b.actual_amount - b.allocated_amount_functional) / NULLIF(b.allocated_amount_functional, 0)) * 100) as avg_variance_pct
                FROM budgets b
                WHERE b.department_id = ?
                  AND b.is_simulation = false
                  AND b.deleted_at IS NULL
                  AND b.created_at >= NOW() - INTERVAL ? MONTH
            ", [$departmentId, $periodsBack]);

            if (!$result || $result->total_budgets == 0) {
                return new ManagerPerformanceScore(
                    departmentId: $departmentId,
                    periodsAnalyzed: 0,
                    budgetsManagedCount: 0,
                    onTargetCount: 0,
                    overBudgetCount: 0,
                    averageVariancePercentage: 0.0,
                    performanceScore: 50.0,
                    tier: 'bronze'
                );
            }

            $onTargetPct = ($result->budgets_on_target / $result->total_budgets) * 100;
            $avgVariance = (float) $result->avg_variance_pct;

            // Score calculation: 70% weight on on-target %, 30% on low variance
            $score = ($onTargetPct * 0.7) + (max(0, 100 - $avgVariance) * 0.3);

            $tier = match (true) {
                $score >= 80 => 'gold',
                $score >= 60 => 'silver',
                default => 'bronze',
            };

            return new ManagerPerformanceScore(
                departmentId: $departmentId,
                periodsAnalyzed: (int) $result->periods_managed,
                budgetsManagedCount: (int) $result->total_budgets,
                onTargetCount: (int) $result->budgets_on_target,
                overBudgetCount: (int) $result->total_budgets - (int) $result->budgets_on_target,
                averageVariancePercentage: $avgVariance,
                performanceScore: $score,
                tier: $tier
            );
        });
    }

    public function getAverageBurnRate(string $departmentId): float
    {
        $cacheKey = "budget.avg_burn_rate.{$departmentId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($departmentId) {
            $result = DB::selectOne("
                SELECT 
                    AVG(daily_spending) as avg_burn_rate
                FROM (
                    SELECT 
                        DATE(bt.created_at) as spending_date,
                        SUM(bt.amount) as daily_spending
                    FROM budget_transactions bt
                    INNER JOIN budgets b ON bt.budget_id = b.id
                    WHERE b.department_id = ?
                      AND bt.transaction_type = ?
                      AND bt.created_at >= NOW() - INTERVAL 90 DAY
                    GROUP BY DATE(bt.created_at)
                ) as daily_totals
            ", [$departmentId, TransactionType::Actual->value]);

            return $result ? (float) $result->avg_burn_rate : 0.0;
        });
    }

    public function getSeasonalityFactor(string $departmentId, string $periodId): float
    {
        $cacheKey = "budget.seasonality.{$departmentId}.{$periodId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($departmentId, $periodId) {
            // Get current period's month
            $periodMonth = DB::selectOne("
                SELECT MONTH(start_date) as month
                FROM periods
                WHERE id = ?
            ", [$periodId]);

            if (!$periodMonth) {
                return 1.0; // Neutral factor
            }

            $month = (int) $periodMonth->month;

            // Calculate historical average for this month
            $result = DB::selectOne("
                SELECT 
                    AVG(bt.amount) as avg_spending_this_month,
                    (SELECT AVG(amount) FROM budget_transactions bt2
                     INNER JOIN budgets b2 ON bt2.budget_id = b2.id
                     WHERE b2.department_id = ?) as avg_spending_overall
                FROM budget_transactions bt
                INNER JOIN budgets b ON bt.budget_id = b.id
                WHERE b.department_id = ?
                  AND bt.transaction_type = ?
                  AND MONTH(bt.created_at) = ?
                  AND bt.created_at >= NOW() - INTERVAL 3 YEAR
            ", [$departmentId, $departmentId, TransactionType::Actual->value, $month]);

            if (!$result || !$result->avg_spending_overall || $result->avg_spending_overall == 0) {
                return 1.0;
            }

            return (float) $result->avg_spending_this_month / (float) $result->avg_spending_overall;
        });
    }

    public function getDepartmentVarianceHistory(string $departmentId, int $periodsBack): array
    {
        return DB::select("
            SELECT 
                b.period_id,
                COUNT(*) as budget_count,
                AVG((b.actual_amount - b.allocated_amount_functional) / NULLIF(b.allocated_amount_functional, 0) * 100) as avg_variance_pct,
                SUM(CASE WHEN b.actual_amount > b.allocated_amount_functional THEN 1 ELSE 0 END) as over_budget_count
            FROM budgets b
            WHERE b.department_id = ?
              AND b.is_simulation = false
              AND b.deleted_at IS NULL
            GROUP BY b.period_id
            ORDER BY b.period_id DESC
            LIMIT ?
        ", [$departmentId, $periodsBack]);
    }

    /**
     * Clear cache for department
     */
    public function clearCache(string $departmentId): void
    {
        Cache::forget("budget.consolidated.{$departmentId}.*");
        Cache::forget("budget.burn_rate.{$departmentId}.*");
        Cache::forget("budget.avg_burn_rate.{$departmentId}");
        Cache::forget("budget.seasonality.{$departmentId}.*");
        Cache::forget("budget.manager_performance.{$departmentId}.*");
    }
}
