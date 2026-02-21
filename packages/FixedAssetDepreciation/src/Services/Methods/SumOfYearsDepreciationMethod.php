<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Services\Methods;

use DateTimeInterface;
use Nexus\FixedAssetDepreciation\Contracts\DepreciationMethodInterface;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;

/**
 * Sum-of-Years-Digits (SYD) Depreciation Method
 *
 * An accelerated depreciation method based on the sum of the years' digits.
 * Results in higher depreciation in early years and lower depreciation later.
 *
 * Formula:
 * - Sum of Years = n(n+1)/2 where n = useful life in years
 * - Year N Depreciation = (Cost - Salvage) × (Remaining Life / Sum of Years)
 *
 * Example for 5-year asset:
 * - Sum = 5 + 4 + 3 + 2 + 1 = 15
 * - Year 1: (Cost - Salvage) × 5/15
 * - Year 2: (Cost - Salvage) × 4/15
 * - Year 3: (Cost - Salvage) × 3/15
 * - Year 4: (Cost - Salvage) × 2/15
 * - Year 5: (Cost - Salvage) × 1/15
 *
 * Tier: 2 (Advanced)
 *
 * Features:
 * - Accelerated depreciation based on time
 * - Uses depreciable base (cost - salvage)
 * - Fractions sum to 1.0 over asset life
 *
 * @package Nexus\FixedAssetDepreciation\Services\Methods
 */
final readonly class SumOfYearsDepreciationMethod implements DepreciationMethodInterface
{
    /**
     * Create a new SumOfYearsDepreciationMethod instance.
     */
    public function __construct() {}

    /**
     * Calculate sum-of-years-digits depreciation for a given period.
     *
     * This method uses the fraction of remaining life to total sum of years
     * to calculate depreciation.
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
     *                       - current_year: Current year of depreciation (1-indexed)
     * @return DepreciationAmount The calculated depreciation amount
     * @throws \InvalidArgumentException If cost is less than salvage value
     * @throws \InvalidArgumentException If useful life is less than 12 months
     */
    public function calculate(
        float $cost,
        float $salvageValue,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        array $options = []
    ): DepreciationAmount {
        $usefulLifeMonths = $options['useful_life_months'] ?? 0;
        $usefulLifeYears = (int) ceil($usefulLifeMonths / 12);
        $currency = $options['currency'] ?? 'USD';
        $accumulatedDepreciation = $options['accumulated_depreciation'] ?? 0.0;
        $currentYear = $options['current_year'] ?? 1;
        
        $depreciableAmount = $cost - $salvageValue;
        $remainingDepreciable = max(0, $depreciableAmount - $accumulatedDepreciation);

        // Check if depreciation is possible
        if ($usefulLifeYears <= 0 || $remainingDepreciable <= 0) {
            return new DepreciationAmount(
                amount: 0.0,
                currency: $currency,
                accumulatedDepreciation: $accumulatedDepreciation
            );
        }

        // Calculate sum of years (1 + 2 + ... + n = n(n+1)/2)
        $sumOfYears = $this->calculateSumOfYears($usefulLifeYears);
        
        // Calculate remaining life for current year
        // If we're in year 3 of a 5-year asset, remaining life is 3
        $remainingLife = max(0, $usefulLifeYears - $currentYear + 1);
        
        // Calculate the fraction for this year
        $fraction = $remainingLife / $sumOfYears;
        
        // Calculate annual depreciation
        $yearlyDepreciation = $depreciableAmount * $fraction;
        
        // Convert to monthly depreciation
        $monthlyDepreciation = $yearlyDepreciation / 12;

        // Ensure we don't exceed remaining depreciable amount
        $depreciationAmount = min($monthlyDepreciation, $remainingDepreciable);

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
        return DepreciationMethodType::SUM_OF_YEARS;
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

        if ($salvageValue >= $cost) {
            $errors[] = 'Salvage value must be less than cost';
        }

        $usefulLifeMonths = $options['useful_life_months'] ?? 0;
        if ($usefulLifeMonths < 12) {
            $errors[] = 'Useful life must be at least 12 months for SYD method';
        }

        return $errors;
    }

    /**
     * Calculate the depreciation rate for this method.
     *
     * Returns the fraction of depreciable amount for the given year.
     *
     * @param int $usefulLifeYears The useful life in years
     * @param array $options Additional method-specific options:
     *                       - current_year: The year to calculate rate for (1-indexed)
     * @return float The annual depreciation rate as a fraction
     */
    public function getDepreciationRate(int $usefulLifeYears, array $options = []): float
    {
        if ($usefulLifeYears <= 0) {
            return 0.0;
        }

        $sumOfYears = $this->calculateSumOfYears($usefulLifeYears);
        $currentYear = $options['current_year'] ?? 1;
        $remainingLife = max(0, $usefulLifeYears - $currentYear + 1);

        return $remainingLife / $sumOfYears;
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
     * SYD does not typically switch to straight-line as it already
     * has a built-in declining schedule based on time.
     *
     * @param float $currentBookValue Current book value
     * @param float $salvageValue Salvage value
     * @param int $remainingMonths Remaining useful life in months
     * @param float $decliningBalanceAmount The calculated declining balance amount
     * @return bool Always false for SYD
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
     * Calculate the sum of years digits.
     *
     * Formula: n(n+1)/2
     *
     * @param int $years The useful life in years
     * @return int The sum of years
     */
    private function calculateSumOfYears(int $years): int
    {
        return ($years * ($years + 1)) / 2;
    }

    /**
     * Get the sum of years for a given useful life.
     *
     * @param int $usefulLifeYears The useful life in years
     * @return int The sum of years
     */
    public function getSumOfYears(int $usefulLifeYears): int
    {
        return $this->calculateSumOfYears($usefulLifeYears);
    }
}
