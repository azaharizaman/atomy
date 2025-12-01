<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\Contracts;

use Nexus\AccountVarianceAnalysis\ValueObjects\VarianceResult;

/**
 * Contract for variance calculation.
 */
interface VarianceCalculatorInterface
{
    /**
     * Calculate variance between actual and budget/prior period.
     *
     * @param float $actual
     * @param float $baseline
     * @return VarianceResult
     */
    public function calculate(float $actual, float $baseline): VarianceResult;

    /**
     * Calculate variance for a set of accounts.
     *
     * @param array<string, float> $actuals
     * @param array<string, float> $baselines
     * @return array<string, VarianceResult>
     */
    public function calculateMultiple(array $actuals, array $baselines): array;
}
