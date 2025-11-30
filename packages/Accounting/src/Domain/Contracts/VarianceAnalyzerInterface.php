<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

use Nexus\Accounting\Domain\ValueObjects\ReportingPeriod;
use Nexus\Accounting\Domain\ValueObjects\VarianceReport;

/**
 * Contract for performing variance analysis.
 *
 * Implementations must analyze variances between actual results
 * and budgets, forecasts, or prior periods.
 */
interface VarianceAnalyzerInterface
{
    /**
     * Analyze budget vs actual variance for the specified period.
     *
     * @param ReportingPeriod $period The period to analyze
     * @param string|null $budgetId Optional specific budget ID
     * @param array<string, mixed> $options Additional analysis options
     *
     * @return VarianceReport The variance analysis report
     *
     * @throws \RuntimeException If analysis fails
     */
    public function analyzeBudgetVariance(
        ReportingPeriod $period,
        ?string $budgetId = null,
        array $options = []
    ): VarianceReport;

    /**
     * Analyze period-over-period variance.
     *
     * @param ReportingPeriod $currentPeriod The current period
     * @param ReportingPeriod $comparisonPeriod The comparison period
     * @param array<string, mixed> $options Additional analysis options
     *
     * @return VarianceReport The variance analysis report
     *
     * @throws \RuntimeException If analysis fails
     */
    public function analyzePeriodVariance(
        ReportingPeriod $currentPeriod,
        ReportingPeriod $comparisonPeriod,
        array $options = []
    ): VarianceReport;

    /**
     * Analyze forecast vs actual variance.
     *
     * @param ReportingPeriod $period The period to analyze
     * @param string $forecastId The forecast ID to compare against
     * @param array<string, mixed> $options Additional analysis options
     *
     * @return VarianceReport The variance analysis report
     *
     * @throws \RuntimeException If analysis fails
     */
    public function analyzeForecastVariance(
        ReportingPeriod $period,
        string $forecastId,
        array $options = []
    ): VarianceReport;
}
