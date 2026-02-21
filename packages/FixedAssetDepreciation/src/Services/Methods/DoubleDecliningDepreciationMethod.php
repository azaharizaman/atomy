<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Services\Methods;

use DateTimeInterface;
use Nexus\FixedAssetDepreciation\Contracts\DepreciationMethodInterface;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;

/**
 * Double Declining Balance (DDB) Depreciation Method
 *
 * This is an accelerated depreciation method that applies a factor of 200%
 * (double) to the straight-line depreciation rate.
 *
 * Formula:
 * - Depreciation Rate = 2 / Useful Life (years)
 * - Year N Depreciation = Beginning Book Value × Depreciation Rate
 *
 * The method automatically switches to straight-line when it produces
 * a higher depreciation amount than continuing with DDB.
 *
 * IMPORTANT: DDB cannot depreciate an asset below its salvage value.
 *
 * Tier: 2 (Advanced)
 *
 * Features:
 * - Automatic switch to straight-line when beneficial
 * - Never depreciates below salvage value
 * - Supports configuration of declining factor
 *
 * @package Nexus\FixedAssetDepreciation\Services\Methods
 */
final readonly class DoubleDecliningDepreciationMethod implements DepreciationMethodInterface
{
    /**
     * Default factor for DDB (200% of straight-line rate).
     */
    private const DEFAULT_FACTOR = 2.0;

    /**
     * Create a new DoubleDecliningDepreciationMethod instance.
     *
     * @param float $decliningFactor The declining balance factor (default 2.0 for double declining)
     * @param bool $switchToStraightLine Whether to switch to straight-line when beneficial
     */
    public function __construct(
        private float $decliningFactor = self::DEFAULT_FACTOR,
        private bool $switchToStraightLine = true,
    ) {}

    /**
     * Calculate double declining balance depreciation for a given period.
     *
     * This method applies the declining balance formula and optionally switches
     * to straight-line when it yields a higher depreciation amount.
     *
     * @param float $cost The original cost of the asset
     * @param float $salvageValue The estimated salvage value at end of life
     * @param DateTimeInterface $startDate The depreciation start date
     * @param DateTimeInterface $endDate The depreciation end date
     * @param array $options Additional options:
     *                       - useful_life_months: Total useful life in months
     *                       - accumulated_depreciation: Current accumulated depreciation
     *                       - remaining_months: Remaining months of useful life
     *                       - currency: Currency code (default USD)
     *                       - current_year: Current year of depreciation (for SYD)
     * @return DepreciationAmount The calculated depreciation amount
     * @throws \InvalidArgumentException If cost is less than salvage value
     * @throws \InvalidArgumentException If useful life is zero or negative
     */
    public function calculate(
        float $cost,
        float $salvageValue,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        array $options = []
    ): DepreciationAmount {
        $usefulLifeMonths = $options['useful_life_months'] ?? 0;
        $usefulLifeYears = $usefulLifeMonths / 12;
        $currency = $options['currency'] ?? 'USD';
        $accumulatedDepreciation = $options['accumulated_depreciation'] ?? 0.0;
        $remainingMonths = $options['remaining_months'] ?? $usefulLifeMonths;
        
        $currentBookValue = $cost - $accumulatedDepreciation;
        $depreciableAmount = $cost - $salvageValue;
        $remainingDepreciable = max(0, $depreciableAmount - $accumulatedDepreciation);

        // Check if depreciation is complete
        if ($usefulLifeYears <= 0 || $remainingDepreciable <= 0 || $currentBookValue <= $salvageValue) {
            return new DepreciationAmount(
                amount: 0.0,
                currency: $currency,
                accumulatedDepreciation: $accumulatedDepreciation
            );
        }

        // Calculate the declining balance rate
        $annualRate = $this->decliningFactor / $usefulLifeYears;
        $monthlyRate = $annualRate / 12;
        
        // Calculate DDB depreciation for this period
        $ddbAmount = $currentBookValue * $monthlyRate;
        
        // Consider switching to straight-line if enabled
        if ($this->switchToStraightLine && $remainingMonths > 0) {
            $slAmount = ($currentBookValue - $salvageValue) / $remainingMonths;
            
            // Switch to SL when it yields higher depreciation
            if ($slAmount > $ddbAmount) {
                $depreciationAmount = $slAmount;
            } else {
                $depreciationAmount = $ddbAmount;
            }
        } else {
            $depreciationAmount = $ddbAmount;
        }

        // Ensure we don't depreciate below salvage value
        $depreciationAmount = min($depreciationAmount, $remainingDepreciable);
        $depreciationAmount = max(0, $depreciationAmount);

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
        return DepreciationMethodType::DOUBLE_DECLINING;
    }

    /**
     * Check if this method supports prorate conventions.
     *
     * @return bool True if the method supports prorating
     */
    public function supportsProrate(): bool
    {
        return true;
    }

    /**
     * Check if this is an accelerated depreciation method.
     *
     * @return bool True if the method is accelerated
     */
    public function isAccelerated(): bool
    {
        return true;
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

        $usefulLifeMonths = $options['useful_life_months'] ?? 0;
        if ($usefulLifeMonths <= 0) {
            $errors[] = 'Useful life months must be positive';
        }

        if ($this->decliningFactor <= 0) {
            $errors[] = 'Declining factor must be positive';
        }

        return $errors;
    }

    /**
     * Calculate the depreciation rate for this method.
     *
     * @param int $usefulLifeYears The useful life in years
     * @param array $options Additional method-specific options
     * @return float The annual depreciation rate
     */
    public function getDepreciationRate(int $usefulLifeYears, array $options = []): float
    {
        if ($usefulLifeYears <= 0) {
            return 0.0;
        }
        return $this->decliningFactor / $usefulLifeYears;
    }

    /**
     * Calculate remaining depreciation for an asset.
     *
     * @param float $currentBookValue Current book value
     * @param float $salvageValue Salvage value
     * @param int $remainingMonths Remaining useful life in months
     * @param array $options Additional method-specific options
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
        return false;
    }

    /**
     * Get the minimum useful life required for this method.
     *
     * @return int Minimum useful life in months
     */
    public function getMinimumUsefulLifeMonths(): int
    {
        return 12;
    }

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
    ): bool {
        if ($remainingMonths <= 0) {
            return false;
        }

        $slAmount = ($currentBookValue - $salvageValue) / $remainingMonths;
        return $slAmount > $decliningBalanceAmount;
    }

    /**
     * Get the declining factor being used.
     *
     * @return float The declining factor
     */
    public function getDecliningFactor(): float
    {
        return $this->decliningFactor;
    }

    /**
     * Check if switch to straight-line is enabled.
     *
     * @return bool True if switch is enabled
     */
    public function isSwitchToStraightLineEnabled(): bool
    {
        return $this->switchToStraightLine;
    }
}
