<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\Services;

use Nexus\AccountVarianceAnalysis\Contracts\SignificanceEvaluatorInterface;
use Nexus\AccountVarianceAnalysis\Enums\SignificanceLevel;
use Nexus\AccountVarianceAnalysis\ValueObjects\SignificanceThreshold;

/**
 * Pure logic for evaluating variance significance.
 */
final readonly class SignificanceEvaluator implements SignificanceEvaluatorInterface
{
    public function evaluate(
        float $varianceAmount,
        float $variancePercentage,
        SignificanceThreshold $threshold
    ): SignificanceLevel {
        $amountExceeds = abs($varianceAmount) > $threshold->getAmountThreshold();
        $percentageExceeds = abs($variancePercentage) > $threshold->getPercentageThreshold();

        if ($threshold->requireBoth()) {
            if ($amountExceeds && $percentageExceeds) {
                return $this->determineLevel($varianceAmount, $variancePercentage, $threshold);
            }
            return SignificanceLevel::LOW;
        }

        if ($amountExceeds || $percentageExceeds) {
            return $this->determineLevel($varianceAmount, $variancePercentage, $threshold);
        }

        return SignificanceLevel::LOW;
    }

    public function isMaterial(float $varianceAmount, float $materialityThreshold): bool
    {
        return abs($varianceAmount) >= $materialityThreshold;
    }

    private function determineLevel(
        float $varianceAmount,
        float $variancePercentage,
        SignificanceThreshold $threshold
    ): SignificanceLevel {
        $amountRatio = abs($varianceAmount) / max($threshold->getAmountThreshold(), 1);
        $percentageRatio = abs($variancePercentage) / max($threshold->getPercentageThreshold(), 1);
        $maxRatio = max($amountRatio, $percentageRatio);

        return match (true) {
            $maxRatio >= 3.0 => SignificanceLevel::CRITICAL,
            $maxRatio >= 2.0 => SignificanceLevel::HIGH,
            $maxRatio >= 1.0 => SignificanceLevel::MEDIUM,
            default => SignificanceLevel::LOW,
        };
    }
}
