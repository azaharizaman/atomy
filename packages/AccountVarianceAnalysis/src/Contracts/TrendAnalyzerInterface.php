<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\Contracts;

use Nexus\AccountVarianceAnalysis\ValueObjects\TrendData;

/**
 * Contract for trend analysis.
 */
interface TrendAnalyzerInterface
{
    /**
     * Analyze trends across multiple periods.
     *
     * @param array<float> $values Values indexed by period
     * @return TrendData
     */
    public function analyze(array $values): TrendData;

    /**
     * Calculate moving average.
     *
     * @param array<float> $values
     * @param int $periods
     * @return array<float>
     */
    public function movingAverage(array $values, int $periods): array;
}
