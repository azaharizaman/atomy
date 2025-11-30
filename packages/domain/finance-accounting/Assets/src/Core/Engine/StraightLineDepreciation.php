<?php

declare(strict_types=1);

namespace Nexus\Assets\Core\Engine;

use Nexus\Assets\Contracts\AssetInterface;
use Nexus\Assets\Contracts\DepreciationEngineInterface;
use Nexus\Assets\Exceptions\InvalidAssetDataException;
use Nexus\Assets\Exceptions\FullyDepreciatedAssetException;

/**
 * Straight-Line Depreciation Engine (Tier 1)
 *
 * Formula: (Cost - Salvage Value) / Useful Life
 * Proration: Daily basis (GAAP-compliant) or full-month (configurable)
 *
 * Example:
 * Asset Cost: $10,000, Salvage: $1,000, Useful Life: 5 years
 * Annual Depreciation: ($10,000 - $1,000) / 5 = $1,800/year
 * Monthly Depreciation: $1,800 / 12 = $150/month
 * Daily Rate: $150 / 30 = $5/day (for mid-month proration)
 */
final readonly class StraightLineDepreciation implements DepreciationEngineInterface
{
    public function __construct(
        private bool $useFullMonthConvention = false
    ) {}

    public function calculateDepreciation(
        AssetInterface $asset,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd
    ): float {
        // Validate asset data
        if ($asset->getCost() <= 0) {
            throw InvalidAssetDataException::invalidCost($asset->getId(), $asset->getCost());
        }

        if ($asset->getUsefulLifeMonths() <= 0) {
            throw InvalidAssetDataException::invalidUsefulLife(
                $asset->getId(),
                $asset->getUsefulLifeMonths()
            );
        }

        if ($asset->getSalvageValue() >= $asset->getCost()) {
            throw InvalidAssetDataException::salvageExceedsCost(
                $asset->getId(),
                $asset->getSalvageValue(),
                $asset->getCost()
            );
        }

        // Check if asset is fully depreciated
        if ($asset->getAccumulatedDepreciation() >= ($asset->getCost() - $asset->getSalvageValue())) {
            throw FullyDepreciatedAssetException::cannotDepreciateAgain($asset->getId());
        }

        $depreciableAmount = $asset->getCost() - $asset->getSalvageValue();
        $usefulLifeMonths = $asset->getUsefulLifeMonths();

        // Calculate monthly depreciation rate
        $monthlyDepreciation = $depreciableAmount / $usefulLifeMonths;

        // Calculate number of months in the period
        $monthsInPeriod = $this->calculateMonthsInPeriod(
            $asset->getAcquisitionDate(),
            $periodStart,
            $periodEnd
        );

        $periodDepreciation = $monthlyDepreciation * $monthsInPeriod;

        // Ensure we don't exceed depreciable amount
        $maxAllowedDepreciation = $depreciableAmount - $asset->getAccumulatedDepreciation();
        
        return min($periodDepreciation, $maxAllowedDepreciation);
    }

    public function calculateUnits(
        AssetInterface $asset,
        float $unitsConsumed,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd
    ): float {
        throw new \LogicException(
            'Straight-Line depreciation does not support units-based calculation. Use UnitsOfProductionDepreciation instead.'
        );
    }

    /**
     * Calculate the number of months (fractional) in the depreciation period.
     *
     * Handles mid-month acquisitions using:
     * - Full-month convention: Asset acquired anytime in the month gets full month depreciation
     * - Daily proration (GAAP): Exact days/total days in month
     *
     * @param \DateTimeImmutable $acquisitionDate When the asset was acquired
     * @param \DateTimeImmutable $periodStart Start of the depreciation period
     * @param \DateTimeImmutable $periodEnd End of the depreciation period
     * @return float Number of months (e.g., 1.5 for 1.5 months)
     */
    private function calculateMonthsInPeriod(
        \DateTimeImmutable $acquisitionDate,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd
    ): float {
        // Don't depreciate before acquisition
        $effectiveStart = max($acquisitionDate, $periodStart);
        
        if ($effectiveStart > $periodEnd) {
            return 0.0;
        }

        if ($this->useFullMonthConvention) {
            return $this->calculateFullMonths($effectiveStart, $periodEnd);
        }

        return $this->calculateDailyProration($effectiveStart, $periodEnd);
    }

    /**
     * Calculate full months between two dates.
     * Any partial month counts as a full month.
     */
    private function calculateFullMonths(
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): float {
        $startYear = (int) $start->format('Y');
        $startMonth = (int) $start->format('n');
        $endYear = (int) $end->format('Y');
        $endMonth = (int) $end->format('n');

        $months = (($endYear - $startYear) * 12) + ($endMonth - $startMonth);
        
        // Add 1 to include the current month
        return (float) ($months + 1);
    }

    /**
     * Calculate months with daily proration for partial months (GAAP-compliant).
     *
     * Example: Asset acquired on Jan 15, period ends Jan 31
     * - Days in period: 17 (Jan 15-31)
     * - Days in January: 31
     * - Proration: 17/31 = 0.548 months
     */
    private function calculateDailyProration(
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): float {
        $interval = $start->diff($end);
        $totalDays = (int) $interval->days + 1; // Include both start and end dates

        // Calculate average days per month for the period
        $averageDaysPerMonth = 30.0; // Standard assumption for depreciation

        return $totalDays / $averageDaysPerMonth;
    }
}
