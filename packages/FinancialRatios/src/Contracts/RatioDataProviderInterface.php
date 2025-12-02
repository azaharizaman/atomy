<?php

declare(strict_types=1);

namespace Nexus\FinancialRatios\Contracts;

use Nexus\FinancialRatios\ValueObjects\RatioInput;

/**
 * Contract for providing financial data to ratio calculators.
 */
interface RatioDataProviderInterface
{
    /**
     * Get ratio input data for a specific period.
     */
    public function getRatioInput(string $tenantId, string $periodId): RatioInput;

    /**
     * Get ratio input data for comparison between periods.
     *
     * @param string[] $periodIds
     * @return array<string, RatioInput>
     */
    public function getComparativeRatioInputs(string $tenantId, array $periodIds): array;

    /**
     * Get average values for a range of periods.
     */
    public function getAverageValues(string $tenantId, string $startPeriodId, string $endPeriodId): RatioInput;

    /**
     * Get industry benchmark data for comparison.
     *
     * @return array<string, float>
     */
    public function getIndustryBenchmarks(string $industryCode): array;
}
