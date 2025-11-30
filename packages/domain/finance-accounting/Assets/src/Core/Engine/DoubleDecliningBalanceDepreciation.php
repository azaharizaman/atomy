<?php

declare(strict_types=1);

namespace Nexus\Assets\Core\Engine;

use Nexus\Assets\Contracts\AssetInterface;
use Nexus\Assets\Contracts\DepreciationEngineInterface;
use Nexus\Assets\Exceptions\InvalidAssetDataException;
use Nexus\Assets\Exceptions\FullyDepreciatedAssetException;

/**
 * Double Declining Balance Depreciation Engine (Tier 2)
 *
 * Accelerated depreciation method that applies a constant rate to the declining book value.
 *
 * Formula: Rate × Beginning Book Value
 * Where Rate = 2 / Useful Life (in years)
 *
 * Special Rules:
 * 1. Never depreciate below salvage value
 * 2. Depreciation slows as book value declines
 * 3. Often switches to straight-line in final years for optimization
 *
 * Example:
 * Asset Cost: $10,000, Salvage: $1,000, Useful Life: 5 years
 * Rate: 2 / 5 = 40% per year
 *
 * Year 1: $10,000 × 40% = $4,000
 * Year 2: $6,000 × 40% = $2,400
 * Year 3: $3,600 × 40% = $1,440
 * Year 4: $2,160 × 40% = $864
 * Year 5: $1,296 → only $296 (stops at salvage value)
 */
final readonly class DoubleDecliningBalanceDepreciation implements DepreciationEngineInterface
{
    public function __construct(
        private bool $switchToStraightLine = true
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

        // Calculate current book value
        $bookValue = $asset->getCost() - $asset->getAccumulatedDepreciation();

        // Check if asset is fully depreciated
        if ($bookValue <= $asset->getSalvageValue()) {
            throw FullyDepreciatedAssetException::cannotDepreciateAgain($asset->getId());
        }

        // Calculate DDB rate (2 / useful life in years)
        $usefulLifeYears = $asset->getUsefulLifeMonths() / 12.0;
        $ddbRate = 2.0 / $usefulLifeYears;

        // Calculate annual DDB depreciation
        $annualDDBDepreciation = $bookValue * $ddbRate;

        // Convert to monthly
        $monthlyDDBDepreciation = $annualDDBDepreciation / 12.0;

        // Calculate months in period
        $monthsInPeriod = $this->calculateMonthsInPeriod($periodStart, $periodEnd);

        $ddbDepreciation = $monthlyDDBDepreciation * $monthsInPeriod;

        // Check if we should switch to straight-line
        if ($this->switchToStraightLine) {
            $straightLineDepreciation = $this->calculateStraightLineAlternative(
                $asset,
                $bookValue,
                $monthsInPeriod
            );

            // Use whichever method gives more depreciation (better for tax purposes)
            $periodDepreciation = max($ddbDepreciation, $straightLineDepreciation);
        } else {
            $periodDepreciation = $ddbDepreciation;
        }

        // Never go below salvage value
        $maxAllowedDepreciation = $bookValue - $asset->getSalvageValue();
        
        return min($periodDepreciation, $maxAllowedDepreciation);
    }

    public function calculateUnits(
        AssetInterface $asset,
        float $unitsConsumed,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd
    ): float {
        throw new \LogicException(
            'Double Declining Balance depreciation does not support units-based calculation. Use UnitsOfProductionDepreciation instead.'
        );
    }

    /**
     * Calculate straight-line depreciation for the remaining book value.
     *
     * This is used for the switch-over optimization in later years.
     *
     * Formula: (Current Book Value - Salvage) / Remaining Life
     */
    private function calculateStraightLineAlternative(
        AssetInterface $asset,
        float $currentBookValue,
        float $monthsInPeriod
    ): float {
        // Calculate months elapsed since acquisition
        $acquisitionDate = $asset->getAcquisitionDate();
        $now = new \DateTimeImmutable();
        $monthsElapsed = $this->calculateMonthsInPeriod($acquisitionDate, $now);

        // Calculate remaining useful life
        $remainingMonths = max(0.0, $asset->getUsefulLifeMonths() - $monthsElapsed);

        if ($remainingMonths <= 0.0) {
            return 0.0;
        }

        // Straight-line for remaining period
        $depreciableAmount = $currentBookValue - $asset->getSalvageValue();
        $monthlyRate = $depreciableAmount / $remainingMonths;

        return $monthlyRate * $monthsInPeriod;
    }

    /**
     * Calculate fractional months between two dates.
     */
    private function calculateMonthsInPeriod(
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): float {
        $interval = $start->diff($end);
        $totalDays = (int) $interval->days + 1;

        return $totalDays / 30.0;
    }
}
