<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\Contracts;

use Nexus\AccountVarianceAnalysis\Enums\SignificanceLevel;
use Nexus\AccountVarianceAnalysis\ValueObjects\SignificanceThreshold;

/**
 * Contract for evaluating variance significance.
 */
interface SignificanceEvaluatorInterface
{
    /**
     * Evaluate the significance of a variance.
     *
     * @param float $varianceAmount
     * @param float $variancePercentage
     * @param SignificanceThreshold $threshold
     * @return SignificanceLevel
     */
    public function evaluate(
        float $varianceAmount,
        float $variancePercentage,
        SignificanceThreshold $threshold
    ): SignificanceLevel;

    /**
     * Check if variance exceeds materiality threshold.
     *
     * @param float $varianceAmount
     * @param float $materialityThreshold
     * @return bool
     */
    public function isMaterial(float $varianceAmount, float $materialityThreshold): bool;
}
