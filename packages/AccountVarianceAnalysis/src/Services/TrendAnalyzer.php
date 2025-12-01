<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\Services;

use Nexus\AccountVarianceAnalysis\Contracts\TrendAnalyzerInterface;
use Nexus\AccountVarianceAnalysis\Enums\TrendDirection;
use Nexus\AccountVarianceAnalysis\ValueObjects\TrendData;

/**
 * Pure logic for trend analysis.
 */
final readonly class TrendAnalyzer implements TrendAnalyzerInterface
{
    public function analyze(array $values): TrendData
    {
        $n = count($values);
        if ($n < 2) {
            return new TrendData(
                direction: TrendDirection::STABLE,
                slope: 0.0,
                rSquared: 0.0,
                values: $values
            );
        }

        $regression = $this->linearRegression($values);
        $direction = $this->determineDirection($regression['slope'], $regression['rSquared']);
        $forecast = $regression['intercept'] + ($regression['slope'] * ($n + 1));

        return new TrendData(
            direction: $direction,
            slope: $regression['slope'],
            rSquared: $regression['rSquared'],
            values: $values,
            forecast: $forecast
        );
    }

    public function movingAverage(array $values, int $periods): array
    {
        if ($periods <= 0 || count($values) < $periods) {
            return $values;
        }

        $result = [];
        $n = count($values);

        for ($i = $periods - 1; $i < $n; $i++) {
            $sum = 0.0;
            for ($j = 0; $j < $periods; $j++) {
                $sum += $values[$i - $j];
            }
            $result[$i] = $sum / $periods;
        }

        return $result;
    }

    private function linearRegression(array $values): array
    {
        $n = count($values);
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;

        $i = 0;
        foreach ($values as $y) {
            $x = $i + 1;
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
            $sumY2 += $y * $y;
            $i++;
        }

        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        if ($denominator == 0) {
            return ['slope' => 0.0, 'intercept' => 0.0, 'rSquared' => 0.0];
        }

        $slope = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
        $intercept = ($sumY - ($slope * $sumX)) / $n;

        $yMean = $sumY / $n;
        $ssTot = 0;
        $ssRes = 0;
        $i = 0;
        foreach ($values as $y) {
            $x = $i + 1;
            $predicted = $intercept + ($slope * $x);
            $ssTot += ($y - $yMean) ** 2;
            $ssRes += ($y - $predicted) ** 2;
            $i++;
        }

        $rSquared = $ssTot != 0 ? 1 - ($ssRes / $ssTot) : 0.0;

        return [
            'slope' => $slope,
            'intercept' => $intercept,
            'rSquared' => max(0, min(1, $rSquared)),
        ];
    }

    private function determineDirection(float $slope, float $rSquared): TrendDirection
    {
        if ($rSquared < 0.5) {
            return TrendDirection::VOLATILE;
        }

        return match (true) {
            $slope > 0.01 => TrendDirection::INCREASING,
            $slope < -0.01 => TrendDirection::DECREASING,
            default => TrendDirection::STABLE,
        };
    }
}
