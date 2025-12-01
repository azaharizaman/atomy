<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\Services;

use Nexus\AccountVarianceAnalysis\Contracts\VarianceCalculatorInterface;
use Nexus\AccountVarianceAnalysis\Enums\VarianceType;
use Nexus\AccountVarianceAnalysis\ValueObjects\VarianceResult;

/**
 * Pure calculation logic for variance.
 */
final readonly class VarianceCalculator implements VarianceCalculatorInterface
{
    public function calculate(float $actual, float $baseline): VarianceResult
    {
        $varianceAmount = $actual - $baseline;
        $variancePercentage = $baseline != 0 
            ? ($varianceAmount / abs($baseline)) * 100 
            : ($varianceAmount != 0 ? 100.0 : 0.0);

        $type = match (true) {
            $varianceAmount > 0 => VarianceType::FAVORABLE,
            $varianceAmount < 0 => VarianceType::UNFAVORABLE,
            default => VarianceType::NEUTRAL,
        };

        return new VarianceResult(
            actual: $actual,
            baseline: $baseline,
            varianceAmount: $varianceAmount,
            variancePercentage: $variancePercentage,
            type: $type
        );
    }

    public function calculateMultiple(array $actuals, array $baselines): array
    {
        $results = [];

        foreach ($actuals as $key => $actual) {
            $baseline = $baselines[$key] ?? 0.0;
            $results[$key] = $this->calculate($actual, $baseline);
        }

        return $results;
    }
}
