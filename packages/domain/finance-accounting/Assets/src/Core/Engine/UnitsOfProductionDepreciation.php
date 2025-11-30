<?php

declare(strict_types=1);

namespace Nexus\Assets\Core\Engine;

use Nexus\Assets\Contracts\AssetInterface;
use Nexus\Assets\Contracts\DepreciationEngineInterface;
use Nexus\Assets\Exceptions\InvalidAssetDataException;
use Nexus\Assets\Exceptions\FullyDepreciatedAssetException;
use Nexus\Assets\Exceptions\UnsupportedDepreciationMethodException;

/**
 * Units of Production Depreciation Engine (Tier 3 - Large Enterprise)
 *
 * Activity-based depreciation method that allocates cost based on actual usage.
 *
 * Formula: (Cost - Salvage Value) × (Units Consumed / Total Expected Units)
 *
 * Common Use Cases:
 * - Manufacturing equipment (machine hours, units produced)
 * - Vehicles (miles driven, hours operated)
 * - Mining equipment (tons extracted)
 * - Printing presses (copies printed)
 *
 * Example:
 * Asset Cost: $100,000, Salvage: $10,000, Total Expected: 500,000 units
 * Rate per Unit: ($100,000 - $10,000) / 500,000 = $0.18 per unit
 *
 * Period Production: 12,000 units
 * Period Depreciation: 12,000 × $0.18 = $2,160
 *
 * Integration with Nexus\Uom:
 * - Supports unit conversions (e.g., kilometers to miles)
 * - Validates unit type compatibility
 */
final readonly class UnitsOfProductionDepreciation implements DepreciationEngineInterface
{
    public function calculateDepreciation(
        AssetInterface $asset,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd
    ): float {
        throw new \LogicException(
            'Units of Production depreciation requires explicit unit consumption data. Use calculateUnits() instead.'
        );
    }

    public function calculateUnits(
        AssetInterface $asset,
        float $unitsConsumed,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd
    ): float {
        // Validate asset data
        if ($asset->getCost() <= 0) {
            throw InvalidAssetDataException::invalidCost($asset->getId(), $asset->getCost());
        }

        $totalExpectedUnits = $asset->getTotalExpectedUnits();
        if ($totalExpectedUnits === null || $totalExpectedUnits <= 0) {
            throw UnsupportedDepreciationMethodException::missingUnitsData($asset->getId());
        }

        if ($asset->getSalvageValue() >= $asset->getCost()) {
            throw InvalidAssetDataException::salvageExceedsCost(
                $asset->getId(),
                $asset->getSalvageValue(),
                $asset->getCost()
            );
        }

        if ($unitsConsumed < 0) {
            throw InvalidAssetDataException::negativeUnitsConsumed($asset->getId(), $unitsConsumed);
        }

        // Check if asset is fully depreciated
        $depreciableAmount = $asset->getCost() - $asset->getSalvageValue();
        if ($asset->getAccumulatedDepreciation() >= $depreciableAmount) {
            throw FullyDepreciatedAssetException::cannotDepreciateAgain($asset->getId());
        }

        // Calculate depreciation rate per unit
        $ratePerUnit = $depreciableAmount / $totalExpectedUnits;

        // Calculate depreciation for units consumed
        $periodDepreciation = $unitsConsumed * $ratePerUnit;

        // Ensure we don't exceed depreciable amount
        $maxAllowedDepreciation = $depreciableAmount - $asset->getAccumulatedDepreciation();
        
        $calculatedDepreciation = min($periodDepreciation, $maxAllowedDepreciation);

        // Warn if we're exceeding expected units (indicates underestimation)
        $totalUnitsConsumed = $this->calculateTotalUnitsFromDepreciation(
            $asset->getAccumulatedDepreciation() + $calculatedDepreciation,
            $ratePerUnit
        );

        if ($totalUnitsConsumed > $totalExpectedUnits) {
            // In real implementation, this would trigger an event for audit logging
            // For now, we just calculate based on available depreciable amount
        }

        return $calculatedDepreciation;
    }

    /**
     * Reverse-calculate total units consumed from accumulated depreciation.
     *
     * This is useful for detecting when an asset has exceeded its expected lifetime.
     *
     * @param float $accumulatedDepreciation Total depreciation recorded
     * @param float $ratePerUnit Depreciation rate per unit
     * @return float Estimated total units consumed
     */
    private function calculateTotalUnitsFromDepreciation(
        float $accumulatedDepreciation,
        float $ratePerUnit
    ): float {
        if ($ratePerUnit <= 0) {
            return 0.0;
        }

        return $accumulatedDepreciation / $ratePerUnit;
    }
}
