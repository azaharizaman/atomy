<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Services\Methods;

use DateTimeInterface;
use Nexus\FixedAssetDepreciation\Contracts\DepreciationMethodInterface;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;

/**
 * Bonus Depreciation Method
 *
 * A first-year additional depreciation deduction that allows businesses to
 * immediately deduct a significant portion of the cost of qualifying property.
 * This is a tax provision that provides an incentive for capital investment.
 *
 * Key Characteristics:
 * - Available for qualifying property (typically new, not used)
 * - Taken in the year property is placed in service
 * - Percentage-based deduction of asset cost
 * - Reduces the depreciable basis for regular depreciation
 *
 * Example:
 * - Asset Cost: $100,000
 * - Bonus Depreciation Rate: 100% (full first-year bonus)
 * - Bonus Depreciation: $100,000
 * - Remaining Basis: $0 (no regular depreciation)
 *
 * With 50% bonus:
 * - Asset Cost: $100,000
 * - Bonus Depreciation: $50,000
 * - Remaining Basis: $50,000 (depreciated using regular method)
 *
 * Tier: 3 (Enterprise)
 *
 * Features:
 * - First-year bonus deduction
 * - Configurable percentage
 * - Reduces remaining depreciable basis
 * - Stackable with regular depreciation methods
 *
 * @package Nexus\FixedAssetDepreciation\Services\Methods
 */
final readonly class BonusDepreciationMethod implements DepreciationMethodInterface
{
    /**
     * Default bonus depreciation percentage (100% for full first-year expensing).
     */
    public const DEFAULT_BONUS_RATE = 1.0;

    /**
     * Common bonus depreciation rates used historically.
     */
    public const RATE_50_PERCENT = 0.50;
    public const RATE_60_PERCENT = 0.60;
    public const RATE_100_PERCENT = 1.00;

    /**
     * Create a new BonusDepreciationMethod instance.
     *
     * @param float $bonusRate The bonus depreciation rate (as decimal, e.g., 0.50 for 50%)
     * @param bool $applyToFullCost Whether to apply bonus to full cost before other deductions
     */
    public function __construct(
        private float $bonusRate = self::DEFAULT_BONUS_RATE,
        private bool $applyToFullCost = true,
    ) {}

    /**
     * Calculate bonus depreciation for a given period.
     *
     * Bonus depreciation is typically taken in the first year (recovery year 1).
     * It provides an immediate deduction of a specified percentage of the asset cost.
     *
     * Note: This method is typically used in combination with other depreciation
     * methods. The bonus reduces the depreciable basis for regular depreciation.
     *
     * @param float $cost The original cost of the asset (basis for bonus depreciation)
     * @param float $salvageValue The salvage value (used to calculate adjusted basis)
     * @param DateTimeInterface $startDate The depreciation start date
     * @param DateTimeInterface $endDate The depreciation end date
     * @param array $options Additional options:
     *                       - recovery_year: Current recovery year (1-indexed)
     *                       - bonus_rate: Override the default bonus rate
     *                       - accumulated_depreciation: Current accumulated depreciation
     *                       - currency: Currency code (default USD)
     *                       - is_new_property: Whether property qualifies as new (default true)
     *                       - section_property: Section 179 or bonus property type
     *                       - first_year_only: Whether bonus only applies in year 1
     * @return DepreciationAmount The calculated depreciation amount
     * @throws \InvalidArgumentException If bonus rate is invalid
     */
    public function calculate(
        float $cost,
        float $salvageValue,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        array $options = []
    ): DepreciationAmount {
        $recoveryYear = $options['recovery_year'] ?? 1;
        $bonusRate = $options['bonus_rate'] ?? $this->bonusRate;
        $currency = $options['currency'] ?? 'USD';
        $accumulatedDepreciation = $options['accumulated_depreciation'] ?? 0.0;
        $isNewProperty = $options['is_new_property'] ?? true;
        $firstYearOnly = $options['first_year_only'] ?? true;

        // Validate bonus rate
        if ($bonusRate < 0 || $bonusRate > 1) {
            return new DepreciationAmount(
                amount: 0.0,
                currency: $currency,
                accumulatedDepreciation: $accumulatedDepreciation
            );
        }

        // Bonus depreciation only applies in year 1 (or specified recovery year)
        $isEligibleYear = $firstYearOnly
            ? ($recoveryYear === 1)
            : ($recoveryYear <= 1);

        if (!$isEligibleYear) {
            return new DepreciationAmount(
                amount: 0.0,
                currency: $currency,
                accumulatedDepreciation: $accumulatedDepreciation
            );
        }

        // Calculate bonus depreciation
        $bonusDepreciation = $cost * $bonusRate;

        // Ensure we don't exceed the cost basis
        $maxBonus = $cost - $accumulatedDepreciation;
        $bonusDepreciation = min($bonusDepreciation, $maxBonus);
        $bonusDepreciation = max(0, $bonusDepreciation);

        // For used property or property that doesn't qualify, return zero
        if (!$isNewProperty && $bonusRate > 0) {
            // Some bonus provisions only apply to new property
            // This is a simplified check; actual qualification depends on specific rules
            return new DepreciationAmount(
                amount: 0.0,
                currency: $currency,
                accumulatedDepreciation: $accumulatedDepreciation
            );
        }

        return new DepreciationAmount(
            amount: round($bonusDepreciation, 2),
            currency: $currency,
            accumulatedDepreciation: $accumulatedDepreciation + $bonusDepreciation
        );
    }

    /**
     * Get the depreciation method type.
     *
     * @return DepreciationMethodType The method type enum
     */
    public function getType(): DepreciationMethodType
    {
        return DepreciationMethodType::BONUS;
    }

    /**
     * Check if this method supports prorate conventions.
     *
     * Bonus depreciation is taken in full in the year of placement,
     * so proration doesn't apply.
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
     * Bonus depreciation is not accelerated - it's an immediate, upfront deduction.
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

        $bonusRate = $options['bonus_rate'] ?? $this->bonusRate;
        if ($bonusRate < 0 || $bonusRate > 1) {
            $errors[] = 'Bonus rate must be between 0 and 1';
        }

        $recoveryYear = $options['recovery_year'] ?? 1;
        if ($recoveryYear < 1) {
            $errors[] = 'Recovery year must be at least 1';
        }

        return $errors;
    }

    /**
     * Calculate the depreciation rate for this method.
     *
     * Returns the bonus depreciation rate.
     *
     * @param int $usefulLifeYears The useful life in years (not used for bonus)
     * @param array $options Additional method-specific options
     * @return float The bonus depreciation rate
     */
    public function getDepreciationRate(int $usefulLifeYears, array $options = []): float
    {
        return $options['bonus_rate'] ?? $this->bonusRate;
    }

    /**
     * Calculate remaining depreciation for an asset.
     *
     * After bonus depreciation, the remaining basis may be depreciated
     * using regular depreciation methods.
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
        // After bonus depreciation, remaining depreciation is the book value
        // (assuming no salvage value for tax purposes)
        return max(0, $currentBookValue);
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
     * Bonus depreciation doesn't require a minimum useful life.
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
     * Bonus depreciation does not switch to straight-line.
     *
     * @param float $currentBookValue Current book value
     * @param float $salvageValue Salvage value
     * @param int $remainingMonths Remaining useful life in months
     * @param float $decliningBalanceAmount The calculated declining balance amount
     * @return bool Always false for bonus depreciation
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
     * Calculate the adjusted depreciable basis after bonus depreciation.
     *
     * This is the amount that remains to be depreciated using
     * regular depreciation methods.
     *
     * @param float $cost The original cost of the asset
     * @param float $bonusRate The bonus depreciation rate applied
     * @return float The remaining depreciable basis
     */
    public function calculateAdjustedBasis(float $cost, float $bonusRate): float
    {
        $bonusDepreciation = $cost * $bonusRate;
        return max(0, $cost - $bonusDepreciation);
    }

    /**
     * Calculate the bonus depreciation amount.
     *
     * @param float $cost The original cost of the asset
     * @param float|null $bonusRate Optional override for bonus rate
     * @return float The bonus depreciation amount
     */
    public function calculateBonusAmount(float $cost, ?float $bonusRate = null): float
    {
        $rate = $bonusRate ?? $this->bonusRate;
        return $cost * $rate;
    }

    /**
     * Get the bonus rate being used.
     *
     * @return float The bonus depreciation rate
     */
    public function getBonusRate(): float
    {
        return $this->bonusRate;
    }

    /**
     * Check if bonus applies to full cost.
     *
     * @return bool True if bonus applies to full cost
     */
    public function appliesToFullCost(): bool
    {
        return $this->applyToFullCost;
    }

    /**
     * Check if bonus is available for the given recovery year.
     *
     * @param int $recoveryYear The recovery year to check
     * @param bool $firstYearOnly Whether bonus is first-year only
     * @return bool True if bonus is available
     */
    public function isAvailableInYear(int $recoveryYear, bool $firstYearOnly = true): bool
    {
        if ($firstYearOnly) {
            return $recoveryYear === 1;
        }
        return $recoveryYear <= 1;
    }

    /**
     * Get the remaining basis after bonus depreciation.
     *
     * @param float $cost Original cost
     * @param float $bonusDepreciation Already taken bonus depreciation
     * @return float Remaining basis for regular depreciation
     */
    public function getRemainingBasis(float $cost, float $bonusDepreciation): float
    {
        return max(0, $cost - $bonusDepreciation);
    }
}
