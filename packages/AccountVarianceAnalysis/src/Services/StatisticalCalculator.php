<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\Services;

/**
 * Pure statistical calculations.
 */
final readonly class StatisticalCalculator
{
    /**
     * Calculate mean of values.
     */
    public function mean(array $values): float
    {
        $count = count($values);
        return $count > 0 ? array_sum($values) / $count : 0.0;
    }

    /**
     * Calculate standard deviation.
     */
    public function standardDeviation(array $values): float
    {
        $count = count($values);
        if ($count < 2) {
            return 0.0;
        }

        $mean = $this->mean($values);
        $sumSquaredDiff = 0.0;

        foreach ($values as $value) {
            $sumSquaredDiff += ($value - $mean) ** 2;
        }

        return sqrt($sumSquaredDiff / ($count - 1));
    }

    /**
     * Calculate coefficient of variation.
     */
    public function coefficientOfVariation(array $values): float
    {
        $mean = $this->mean($values);
        if ($mean == 0) {
            return 0.0;
        }

        return ($this->standardDeviation($values) / abs($mean)) * 100;
    }

    /**
     * Calculate Z-score for a value.
     */
    public function zScore(float $value, float $mean, float $stdDev): float
    {
        if ($stdDev == 0) {
            return 0.0;
        }

        return ($value - $mean) / $stdDev;
    }

    /**
     * Identify outliers using IQR method.
     */
    public function identifyOutliers(array $values, float $factor = 1.5): array
    {
        if (count($values) < 4) {
            return [];
        }

        sort($values);
        $q1 = $this->percentile($values, 25);
        $q3 = $this->percentile($values, 75);
        $iqr = $q3 - $q1;

        $lowerBound = $q1 - ($factor * $iqr);
        $upperBound = $q3 + ($factor * $iqr);

        $outliers = [];
        foreach ($values as $value) {
            if ($value < $lowerBound || $value > $upperBound) {
                $outliers[] = $value;
            }
        }

        return $outliers;
    }

    /**
     * Calculate percentile.
     */
    public function percentile(array $values, float $percentile): float
    {
        $count = count($values);
        if ($count === 0) {
            return 0.0;
        }

        sort($values);
        $index = ($percentile / 100) * ($count - 1);
        $lower = (int) floor($index);
        $upper = (int) ceil($index);

        if ($lower === $upper) {
            return $values[$lower];
        }

        return $values[$lower] + ($index - $lower) * ($values[$upper] - $values[$lower]);
    }
}
