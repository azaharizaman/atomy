<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Services\Methods;

use DateTimeInterface;
use Nexus\FixedAssetDepreciation\Contracts\DepreciationMethodInterface;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;

/**
 * Units of Production Depreciation Method
 *
 * Usage-based depreciation method where depreciation is calculated based on
 * actual usage (units produced, hours used, miles driven, etc.) rather than time.
 * This method is particularly useful for assets whose wear and tear is more closely
 * related to usage than to the passage of time.
 *
 * Formula:
 * Depreciation per Unit = (Cost - Salvage Value) / Total Expected Units
 * Period Depreciation = Depreciation per Unit × Units Produced in Period
 *
 * Example:
 * - Asset Cost: $100,000
 * - Salvage Value: $10,000
 * - Total Expected Units: 90,000 units
 * - Depreciable Amount: $100,000 - $10,000 = $90,000
 * - Depreciation per Unit: $90,000 / 90,000 = $1.00 per unit
 * - If 10,000 units produced in year 1: $1.00 × 10,000 = $10,000
 *
 * Tier: 3 (Enterprise)
 *
 * Features:
 * - Usage-based depreciation (not time-based)
 * - Requires unit tracking data
 * - Accurate depreciation based on actual asset usage
 * - Ideal for manufacturing equipment, vehicles, etc.
 *
 * @package Nexus\FixedAssetDepreciation\Services\Methods
 */
final readonly class UnitsOfProductionDepreciationMethod implements DepreciationMethodInterface
{
    /**
     * Create a new UnitsOfProductionDepreciationMethod instance.
     */
    public function __construct() {}

    /**
     * Calculate units of production depreciation for a given period.
     *
     * This method calculates depreciation based on actual production or usage
     * rather than time. The depreciation is proportional to the number of units
     * produced (or hours used, miles driven, etc.) during the period.
     *
     * Formula: (Cost - Salvage) × (Units Produced / Total Expected Units)
     *
     * @param float $cost The original cost of the asset
     * @param float $salvageValue The estimated salvage value at end of life
     * @param DateTimeInterface $startDate The depreciation start date
     * @param DateTimeInterface $endDate The depreciation end date
     * @param array $options Additional options:
     *                       - units_produced: Units produced/used in this period (required)
     *                       - total_expected_units: Total expected units over asset life (required)
     *                       - accumulated_depreciation: Current accumulated depreciation
     *                       - currency: Currency code (default USD)
     *                       - unit_type: Type of unit ('units', 'hours', 'miles', etc.)
     * @return DepreciationAmount The calculated depreciation amount
     * @throws \InvalidArgumentException If units_produced or total_expected_units is not provided
     * @throws \InvalidArgumentException If total_expected_units is zero or negative
     */
    public function calculate(
        float $cost,
        float $salvageValue,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        array $options = []
    ): DepreciationAmount {
        $currency = $options['currency'] ?? 'USD';
        $accumulatedDepreciation = $options['accumulated_depreciation'] ?? 0.0;
        $unitsProduced = $options['units_produced'] ?? 0;
        $totalExpectedUnits = $options['total_expected_units'] ?? 0;

        $depreciableAmount = $cost - $salvageValue;
        $remainingDepreciable = max(0, $depreciableAmount - $accumulatedDepreciation);

        // Validate that we have valid inputs
        if ($totalExpectedUnits <= 0 || $unitsProduced <= 0 || $remainingDepreciable <= 0) {
            return new DepreciationAmount(
                amount: 0.0,
                currency: $currency,
                accumulatedDepreciation: $accumulatedDepreciation
            );
        }

        // Calculate depreciation per unit
        // Formula: (Cost - Salvage) / Total Expected Units
        $depreciationPerUnit = $depreciableAmount / $totalExpectedUnits;

        // Calculate depreciation for this period
        // Formula: Depreciation per Unit × Units Produced in Period
        $depreciationAmount = $depreciationPerUnit * $unitsProduced;

        // Ensure we don't exceed remaining depreciable amount
        $depreciationAmount = min($depreciationAmount, $remainingDepreciable);

        return new DepreciationAmount(
            amount: round($depreciationAmount, 2),
            currency: $currency,
            accumulatedDepreciation: $accumulatedDepreciation + $depreciationAmount
        );
    }

    /**
     * Get the depreciation method type.
     *
     * @return DepreciationMethodType The method type enum
     */
    public function getType(): DepreciationMethodType
    {
        return DepreciationMethodType::UNITS_OF_PRODUCTION;
    }

    /**
     * Check if this method supports prorate conventions.
     *
     * Units of Production does not use time-based proration,
     * as depreciation is based on actual usage.
     *
     * @return bool True if the method supports prorating
     */
    public function supportsProrate(): bool
    {
        return false;
    }

    /**
     * Check if this is an accelerated depreciation method.
     *
     * UOP is not accelerated - it allocates depreciation
     * based on actual usage, which may vary each period.
     *
     * @return bool True if the method is accelerated
     */
    public function isAccelerated(): bool
    {
        return false;
    }

    /**
     * Validate depreciation parameters for this method.
     *
     * @param float $cost The asset cost
     * @param float $salvageValue The salvage value
     * @param array $options Method-specific options
     * @return bool True if parameters are valid
     */
    public function validate(float $cost, float $salvageValue, array $options): bool
    {
        return count($this->getValidationErrors($cost, $salvageValue, $options)) === 0;
    }

    /**
     * Get validation errors for the given parameters.
     *
     * @param float $cost The asset cost
     * @param float $salvageValue The salvage value
     * @param array $options Method-specific options
     * @return array<string> Array of validation error messages
     */
    public function getValidationErrors(float $cost, float $salvageValue, array $options): array
    {
        $errors = [];

        if ($cost <= 0) {
            $errors[] = 'Cost must be positive';
        }

        if ($salvageValue < 0) {
            $errors[] = 'Salvage value cannot be negative';
        }

        if ($salvageValue >= $cost) {
            $errors[] = 'Salvage value must be less than cost';
        }

        $totalExpectedUnits = $options['total_expected_units'] ?? 0;
        if ($totalExpectedUnits <= 0) {
            $errors[] = 'Total expected units must be positive for UOP method';
        }

        $unitsProduced = $options['units_produced'] ?? 0;
        if ($unitsProduced < 0) {
            $errors[] = 'Units produced cannot be negative';
        }

        return $errors;
    }

    /**
     * Calculate the depreciation rate for this method.
     *
     * Returns the depreciation amount per unit.
     *
     * @param int $usefulLifeYears The useful life in years (not used for UOP)
     * @param array $options Additional method-specific options:
     *                       - total_expected_units: Total expected units
     *                       - cost: Asset cost
     *                       - salvage_value: Salvage value
     * @return float The depreciation rate per unit
     */
    public function getDepreciationRate(int $usefulLifeYears, array $options = []): float
    {
        $totalExpectedUnits = $options['total_expected_units'] ?? 0;
        if ($totalExpectedUnits <= 0) {
            return 0.0;
        }

        $cost = $options['cost'] ?? 0.0;
        $salvageValue = $options['salvage_value'] ?? 0.0;

        return ($cost - $salvageValue) / $totalExpectedUnits;
    }

    /**
     * Calculate remaining depreciation for an asset.
     *
     * @param float $currentBookValue Current book value
     * @param float $salvageValue Salvage value
     * @param int $remainingMonths Remaining useful life in months (not used for UOP)
     * @param array $options Additional method-specific options:
     *                       - remaining_units: Remaining units to be produced
     * @return float The remaining depreciation amount
     */
    public function calculateRemainingDepreciation(
        float $currentBookValue,
        float $salvageValue,
        int $remainingMonths,
        array $options = []
    ): float {
        return max(0, $currentBookValue - $salvageValue);
    }

    /**
     * Check if this method requires units of production data.
     *
     * @return bool True if units data is required
     */
    public function requiresUnitsData(): bool
    {
        return true;
    }

    /**
     * Get the minimum useful life required for this method.
     *
     * UOP does not require a minimum useful life in months,
     * as depreciation is based on units produced.
     *
     * @return int Minimum useful life in months
     */
    public function getMinimumUsefulLifeMonths(): int
    {
        return 0;
    }

    /**
     * Check if the method should switch to straight-line.
     *
     * UOP does not switch to straight-line as it is usage-based.
     *
     * @param float $currentBookValue Current book value
     * @param float $salvageValue Salvage value
     * @param int $remainingMonths Remaining useful life in months
     * @param float $decliningBalanceAmount The calculated declining balance amount
     * @return bool Always false for UOP
     */
    public function shouldSwitchToStraightLine(
        float $currentBookValue,
        float $salvageValue,
        int $remainingMonths,
        float $decliningBalanceAmount
    ): bool {
        return false;
    }

    /**
     * Calculate depreciation per unit.
     *
     * @param float $cost The asset cost
     * @param float $salvageValue The salvage value
     * @param float $totalExpectedUnits Total expected units over asset life
     * @return float Depreciation per unit
     */
    public function getDepreciationPerUnit(
        float $cost,
        float $salvageValue,
        float $totalExpectedUnits
    ): float {
        if ($totalExpectedUnits <= 0) {
            return 0.0;
        }

        return ($cost - $salvageValue) / $totalExpectedUnits;
    }
}
