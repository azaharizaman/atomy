<?php

declare(strict_types=1);

namespace Nexus\Budget\ValueObjects;

/**
 * Budget Dashboard Metrics value object
 * 
 * Immutable representation of aggregated dashboard metrics.
 */
final readonly class BudgetDashboardMetrics
{
    /**
     * @param float $averageBurnRate Average burn rate across budgets
     * @param array<string, float> $departmentVariances Department ID => variance percentage
     * @param array<array{department_id: string, variance_pct: float}> $topOverspenders Top overspending departments
     * @param array<array{department_id: string, variance_pct: float}> $topUnderspenders Top underspending departments
     */
    public function __construct(
        private float $averageBurnRate,
        private array $departmentVariances,
        private array $topOverspenders,
        private array $topUnderspenders
    ) {}

    public function getAverageBurnRate(): float
    {
        return $this->averageBurnRate;
    }

    /**
     * @return array<string, float>
     */
    public function getDepartmentVariances(): array
    {
        return $this->departmentVariances;
    }

    /**
     * @return array<array{department_id: string, variance_pct: float}>
     */
    public function getTopOverspenders(): array
    {
        return $this->topOverspenders;
    }

    /**
     * @return array<array{department_id: string, variance_pct: float}>
     */
    public function getTopUnderspenders(): array
    {
        return $this->topUnderspenders;
    }

    /**
     * Get variance for specific department
     */
    public function getDepartmentVariance(string $departmentId): ?float
    {
        return $this->departmentVariances[$departmentId] ?? null;
    }

    /**
     * Get overall health score (0-100)
     */
    public function getHealthScore(): float
    {
        $totalDepartments = count($this->departmentVariances);
        if ($totalDepartments === 0) {
            return 100.0;
        }

        $healthyCount = 0;
        foreach ($this->departmentVariances as $variance) {
            if (abs($variance) <= 10.0) { // Within 10% is considered healthy
                $healthyCount++;
            }
        }

        return ($healthyCount / $totalDepartments) * 100;
    }
}
