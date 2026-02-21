<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Contracts;

use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;

/**
 * Interface for depreciation method implementations.
 *
 * This interface defines the contract for all depreciation calculation methods.
 * Each method (Straight-Line, Declining Balance, Sum-of-Years, etc.) must
 * implement this interface to provide consistent calculation behavior.
 *
 * The interface supports the Strategy pattern, allowing different depreciation
 * algorithms to be used interchangeably based on asset or business requirements.
 *
 * @package Nexus\FixedAssetDepreciation\Contracts
 */
interface DepreciationMethodInterface
{
    /**
     * Calculate depreciation for a single period.
     *
     * Computes the depreciation amount for the specified period based on
     * the method's algorithm. The calculation considers the asset's cost,
     * salvage value, and the time period being depreciated.
     *
     * @param float $cost The original cost of the asset
     * @param float $salvageValue The expected salvage value at end of life
     * @param \DateTimeInterface $startDate The start date for depreciation
     * @param \DateTimeInterface $endDate The end date for this period
     * @param array $options Additional method-specific options:
     *                       - useful_life_months: int Total useful life in months
     *                       - accumulated_depreciation: float Current accumulated depreciation
     *                       - currency: string Currency code (default: 'USD')
     *                       - prorate_daily: bool Whether to prorate daily
     *                       - units_produced: int For UOP method
     *                       - total_units: int For UOP method
     *                       - interest_rate: float For annuity method
     * @return DepreciationAmount The calculated depreciation amount
     * @throws \Nexus\FixedAssetDepreciation\Exceptions\DepreciationCalculationException
     */
    public function calculate(
        float $cost,
        float $salvageValue,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        array $options = []
    ): DepreciationAmount;

    /**
     * Get the depreciation method type.
     *
     * Returns the enum value representing this depreciation method.
     * Used for method identification and validation.
     *
     * @return DepreciationMethodType The method type enum
     */
    public function getType(): DepreciationMethodType;

    /**
     * Check if this method supports prorate conventions.
     *
     * Some depreciation methods (like Straight-Line) support prorating
     * for mid-period acquisitions, while others (like MACRS) have
     * built-in conventions.
     *
     * @return bool True if the method supports prorating
     */
    public function supportsProrate(): bool;

    /**
     * Check if this is an accelerated depreciation method.
     *
     * Accelerated methods (DDB, SYD) result in higher depreciation
     * in early years compared to straight-line.
     *
     * @return bool True if the method is accelerated
     */
    public function isAccelerated(): bool;

    /**
     * Validate depreciation parameters for this method.
     *
     * Checks if the provided parameters are valid for this depreciation
     * method. Returns true if valid, false otherwise.
     *
     * @param float $cost The asset cost
     * @param float $salvageValue The salvage value
     * @param array $options Method-specific options
     * @return bool True if parameters are valid
     */
    public function validate(float $cost, float $salvageValue, array $options): bool;

    /**
     * Get validation errors for the given parameters.
     *
     * Returns an array of validation error messages for the provided
     * parameters. Empty array indicates valid parameters.
     *
     * @param float $cost The asset cost
     * @param float $salvageValue The salvage value
     * @param array $options Method-specific options
     * @return array<string> Array of validation error messages
     */
    public function getValidationErrors(float $cost, float $salvageValue, array $options): array;

    /**
     * Calculate the depreciation rate for this method.
     *
     * Returns the annual depreciation rate as a decimal (e.g., 0.20 for 20%).
     * For methods like Straight-Line, this is simply 1/useful_life_years.
     * For declining balance, this is the declining factor divided by useful life.
     *
     * @param int $usefulLifeYears The useful life in years
     * @param array $options Additional method-specific options
     * @return float The annual depreciation rate
     */
    public function getDepreciationRate(int $usefulLifeYears, array $options = []): float;

    /**
     * Calculate remaining depreciation for an asset.
     *
     * Computes the total remaining depreciation that can be taken
     * on an asset given its current book value and remaining life.
     *
     * @param float $currentBookValue The current net book value
     * @param float $salvageValue The salvage value
     * @param int $remainingMonths Remaining months of useful life
     * @param array $options Additional method-specific options
     * @return float The remaining depreciation amount
     */
    public function calculateRemainingDepreciation(
        float $currentBookValue,
        float $salvageValue,
        int $remainingMonths,
        array $options = []
    ): float;

    /**
     * Check if this method requires units of production data.
     *
     * The Units of Production method requires actual production data
     * to calculate depreciation. Other methods do not.
     *
     * @return bool True if units data is required
     */
    public function requiresUnitsData(): bool;

    /**
     * Get the minimum useful life required for this method.
     *
     * Some methods have minimum useful life requirements to produce
     * meaningful depreciation amounts.
     *
     * @return int Minimum useful life in months
     */
    public function getMinimumUsefulLifeMonths(): int;

    /**
     * Check if the method should switch to straight-line.
     *
     * For declining balance methods, it's often beneficial to switch
     * to straight-line when the straight-line amount exceeds the
     * declining balance amount.
     *
     * @param float $currentBookValue Current book value
     * @param float $salvageValue Salvage value
     * @param int $remainingMonths Remaining useful life in months
     * @param float $decliningBalanceAmount The calculated declining balance amount
     * @return bool True if should switch to straight-line
     */
    public function shouldSwitchToStraightLine(
        float $currentBookValue,
        float $salvageValue,
        int $remainingMonths,
        float $decliningBalanceAmount
    ): bool;
}
