<?php

declare(strict_types=1);

namespace Nexus\FinancialRatios\Services;

use Nexus\FinancialRatios\ValueObjects\RatioResult;
use Nexus\FinancialRatios\ValueObjects\RatioBenchmark;
use Nexus\FinancialRatios\ValueObjects\HealthIndicator;
use Nexus\FinancialRatios\Enums\RatioType;
use Nexus\FinancialRatios\Enums\BenchmarkSource;
use Nexus\FinancialRatios\Exceptions\BenchmarkNotFoundException;

/**
 * Compares calculated ratios against benchmarks
 */
final readonly class RatioBenchmarker
{
    /**
     * @param array<string, RatioBenchmark> $benchmarks Predefined benchmarks by ratio type
     */
    public function __construct(
        private array $benchmarks = []
    ) {}

    /**
     * Compare a ratio against its benchmark
     *
     * @param RatioResult $ratio The calculated ratio
     * @param RatioBenchmark|null $benchmark Optional specific benchmark (uses default if null)
     * @return HealthIndicator
     * @throws BenchmarkNotFoundException
     */
    public function compare(RatioResult $ratio, ?RatioBenchmark $benchmark = null): HealthIndicator
    {
        $benchmark = $benchmark ?? $this->getBenchmark($ratio->type);

        $deviation = $this->calculateDeviation($ratio->value, $benchmark);
        $percentile = $this->calculatePercentile($ratio->value, $benchmark);
        $status = $this->determineStatus($ratio->value, $benchmark, $ratio->type);
        $recommendation = $this->generateRecommendation($ratio, $benchmark, $status);

        return new HealthIndicator(
            ratioType: $ratio->type,
            actualValue: $ratio->value,
            benchmarkValue: $benchmark->value,
            deviation: $deviation,
            deviationPercent: $benchmark->value != 0 ? ($deviation / abs($benchmark->value)) * 100 : 0,
            percentile: $percentile,
            status: $status,
            source: $benchmark->source,
            recommendation: $recommendation
        );
    }

    /**
     * Compare multiple ratios against their benchmarks
     *
     * @param array<RatioResult> $ratios
     * @return array<string, HealthIndicator>
     */
    public function compareMultiple(array $ratios): array
    {
        $results = [];

        foreach ($ratios as $ratio) {
            try {
                $results[$ratio->type->value] = $this->compare($ratio);
            } catch (BenchmarkNotFoundException) {
                // Skip ratios without benchmarks
                continue;
            }
        }

        return $results;
    }

    /**
     * Get overall health score based on multiple ratio comparisons
     *
     * @param array<HealthIndicator> $indicators
     * @return array{score: float, grade: string, summary: string}
     */
    public function calculateOverallHealth(array $indicators): array
    {
        if (empty($indicators)) {
            return [
                'score' => 0,
                'grade' => 'N/A',
                'summary' => 'No indicators available for health assessment',
            ];
        }

        $totalScore = 0;
        $weights = $this->getIndicatorWeights();

        foreach ($indicators as $indicator) {
            $weight = $weights[$indicator->ratioType->value] ?? 1.0;
            $indicatorScore = $this->scoreIndicator($indicator);
            $totalScore += $indicatorScore * $weight;
        }

        $totalWeight = array_sum(array_map(
            fn($i) => $weights[$i->ratioType->value] ?? 1.0,
            $indicators
        ));

        $averageScore = $totalWeight > 0 ? $totalScore / $totalWeight : 0;
        $normalizedScore = min(100, max(0, $averageScore));

        return [
            'score' => round($normalizedScore, 1),
            'grade' => $this->scoreToGrade($normalizedScore),
            'summary' => $this->generateHealthSummary($normalizedScore, $indicators),
        ];
    }

    /**
     * Get benchmark for a ratio type
     *
     * @throws BenchmarkNotFoundException
     */
    public function getBenchmark(RatioType $type): RatioBenchmark
    {
        if (isset($this->benchmarks[$type->value])) {
            return $this->benchmarks[$type->value];
        }

        // Return default benchmarks if not configured
        return $this->getDefaultBenchmark($type);
    }

    /**
     * Get default industry benchmarks
     */
    private function getDefaultBenchmark(RatioType $type): RatioBenchmark
    {
        $defaults = [
            // Liquidity
            RatioType::CURRENT_RATIO->value => new RatioBenchmark(
                ratioType: RatioType::CURRENT_RATIO,
                value: 2.0,
                source: BenchmarkSource::INDUSTRY_AVERAGE,
                minAcceptable: 1.0,
                maxAcceptable: 3.0,
                optimalRange: [1.5, 2.5]
            ),
            RatioType::QUICK_RATIO->value => new RatioBenchmark(
                ratioType: RatioType::QUICK_RATIO,
                value: 1.0,
                source: BenchmarkSource::INDUSTRY_AVERAGE,
                minAcceptable: 0.5,
                maxAcceptable: 2.0,
                optimalRange: [1.0, 1.5]
            ),

            // Profitability
            RatioType::NET_PROFIT_MARGIN->value => new RatioBenchmark(
                ratioType: RatioType::NET_PROFIT_MARGIN,
                value: 0.10,
                source: BenchmarkSource::INDUSTRY_AVERAGE,
                minAcceptable: 0.02,
                maxAcceptable: null,
                optimalRange: [0.10, 0.20]
            ),
            RatioType::RETURN_ON_EQUITY->value => new RatioBenchmark(
                ratioType: RatioType::RETURN_ON_EQUITY,
                value: 0.15,
                source: BenchmarkSource::INDUSTRY_AVERAGE,
                minAcceptable: 0.08,
                maxAcceptable: null,
                optimalRange: [0.15, 0.25]
            ),
            RatioType::RETURN_ON_ASSETS->value => new RatioBenchmark(
                ratioType: RatioType::RETURN_ON_ASSETS,
                value: 0.05,
                source: BenchmarkSource::INDUSTRY_AVERAGE,
                minAcceptable: 0.02,
                maxAcceptable: null,
                optimalRange: [0.05, 0.15]
            ),

            // Leverage
            RatioType::DEBT_TO_EQUITY->value => new RatioBenchmark(
                ratioType: RatioType::DEBT_TO_EQUITY,
                value: 1.0,
                source: BenchmarkSource::INDUSTRY_AVERAGE,
                minAcceptable: null,
                maxAcceptable: 2.0,
                optimalRange: [0.5, 1.5]
            ),
            RatioType::INTEREST_COVERAGE->value => new RatioBenchmark(
                ratioType: RatioType::INTEREST_COVERAGE,
                value: 3.0,
                source: BenchmarkSource::INDUSTRY_AVERAGE,
                minAcceptable: 1.5,
                maxAcceptable: null,
                optimalRange: [3.0, 10.0]
            ),

            // Efficiency
            RatioType::ASSET_TURNOVER->value => new RatioBenchmark(
                ratioType: RatioType::ASSET_TURNOVER,
                value: 1.0,
                source: BenchmarkSource::INDUSTRY_AVERAGE,
                minAcceptable: 0.5,
                maxAcceptable: null,
                optimalRange: [1.0, 2.0]
            ),
            RatioType::INVENTORY_TURNOVER->value => new RatioBenchmark(
                ratioType: RatioType::INVENTORY_TURNOVER,
                value: 6.0,
                source: BenchmarkSource::INDUSTRY_AVERAGE,
                minAcceptable: 2.0,
                maxAcceptable: null,
                optimalRange: [4.0, 8.0]
            ),
        ];

        if (!isset($defaults[$type->value])) {
            throw new BenchmarkNotFoundException("No benchmark available for ratio type: {$type->value}");
        }

        return $defaults[$type->value];
    }

    private function calculateDeviation(float $actual, RatioBenchmark $benchmark): float
    {
        return $actual - $benchmark->value;
    }

    private function calculatePercentile(float $actual, RatioBenchmark $benchmark): float
    {
        // Simplified percentile calculation based on optimal range
        if ($benchmark->optimalRange === null) {
            return 50.0;
        }

        [$min, $max] = $benchmark->optimalRange;
        $range = $max - $min;

        if ($range == 0) {
            return $actual == $min ? 50.0 : ($actual > $min ? 75.0 : 25.0);
        }

        $position = ($actual - $min) / $range;

        // Convert to percentile (0-100 scale)
        return max(0, min(100, $position * 100));
    }

    private function determineStatus(float $actual, RatioBenchmark $benchmark, RatioType $type): string
    {
        // Check if within optimal range
        if ($benchmark->optimalRange !== null) {
            [$min, $max] = $benchmark->optimalRange;
            if ($actual >= $min && $actual <= $max) {
                return 'optimal';
            }
        }

        // Check acceptable bounds
        $belowMin = $benchmark->minAcceptable !== null && $actual < $benchmark->minAcceptable;
        $aboveMax = $benchmark->maxAcceptable !== null && $actual > $benchmark->maxAcceptable;

        if ($belowMin || $aboveMax) {
            return 'critical';
        }

        // Compare to benchmark value
        $deviation = abs($actual - $benchmark->value);
        $threshold = abs($benchmark->value) * 0.2; // 20% threshold

        if ($deviation <= $threshold) {
            return 'good';
        }

        return 'warning';
    }

    private function generateRecommendation(RatioResult $ratio, RatioBenchmark $benchmark, string $status): string
    {
        if ($status === 'optimal' || $status === 'good') {
            return "Ratio is within acceptable range. Continue monitoring.";
        }

        $isBelow = $ratio->value < $benchmark->value;
        $ratioLabel = $ratio->type->getLabel();

        return match ($ratio->type->getCategory()->value) {
            'liquidity' => $isBelow
                ? "Improve liquidity by increasing current assets or reducing current liabilities"
                : "Consider investing excess liquidity for better returns",
            'profitability' => $isBelow
                ? "Focus on improving margins through cost reduction or revenue enhancement"
                : "Excellent profitability - ensure sustainability",
            'leverage' => $isBelow
                ? "Consider utilizing more debt financing if appropriate for growth"
                : "Reduce debt levels to improve financial stability",
            'efficiency' => $isBelow
                ? "Improve asset utilization through better operations management"
                : "High efficiency - ensure quality is maintained",
            default => "Review {$ratioLabel} and develop improvement strategies",
        };
    }

    private function scoreIndicator(HealthIndicator $indicator): float
    {
        return match ($indicator->status) {
            'optimal' => 100,
            'good' => 80,
            'warning' => 50,
            'critical' => 20,
            default => 50,
        };
    }

    /**
     * @return array<string, float>
     */
    private function getIndicatorWeights(): array
    {
        return [
            RatioType::CURRENT_RATIO->value => 1.2,
            RatioType::QUICK_RATIO->value => 1.0,
            RatioType::NET_PROFIT_MARGIN->value => 1.5,
            RatioType::RETURN_ON_EQUITY->value => 1.5,
            RatioType::RETURN_ON_ASSETS->value => 1.3,
            RatioType::DEBT_TO_EQUITY->value => 1.2,
            RatioType::INTEREST_COVERAGE->value => 1.4,
            RatioType::ASSET_TURNOVER->value => 1.0,
            RatioType::INVENTORY_TURNOVER->value => 0.8,
        ];
    }

    private function scoreToGrade(float $score): string
    {
        return match (true) {
            $score >= 90 => 'A+',
            $score >= 80 => 'A',
            $score >= 70 => 'B',
            $score >= 60 => 'C',
            $score >= 50 => 'D',
            default => 'F',
        };
    }

    /**
     * @param array<HealthIndicator> $indicators
     */
    private function generateHealthSummary(float $score, array $indicators): string
    {
        $criticalCount = count(array_filter($indicators, fn($i) => $i->status === 'critical'));
        $warningCount = count(array_filter($indicators, fn($i) => $i->status === 'warning'));
        $optimalCount = count(array_filter($indicators, fn($i) => $i->status === 'optimal'));

        if ($criticalCount > 0) {
            return "Financial health requires attention. {$criticalCount} critical indicator(s) detected.";
        }

        if ($warningCount > 0) {
            return "Generally healthy with {$warningCount} area(s) requiring monitoring.";
        }

        if ($optimalCount === count($indicators)) {
            return "Excellent financial health. All indicators within optimal range.";
        }

        return "Good overall financial health with room for improvement in some areas.";
    }
}
