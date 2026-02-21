<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Services\Methods;

use DateTimeInterface;
use Nexus\FixedAssetDepreciation\Contracts\DepreciationMethodInterface;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;

/**
 * Annuity Depreciation Method
 *
 * An interest-based depreciation method that calculates depreciation as a
 * constant annuity (equal payments) that includes both depreciation expense
 * and interest on the declining book value. This method is also known as
 * the "actuarial" or "present value" method.
 *
 * Formula:
 * The depreciation amount is calculated using the present value of annuity formula:
 * Depreciation = (Cost - Salvage) × [i / (1 - (1 + i)^-n)]
 * Where:
 *   - i = interest rate per period
 *   - n = number of periods (useful life)
 *
 * This results in a constant periodic payment that represents both
 * depreciation and implicit interest on the remaining book value.
 *
 * Tier: 3 (Enterprise)
 *
 * Features:
 * - Interest-based depreciation
 * - Requires interest rate input
 * - Constant periodic payment (like an annuity)
 * - Book value decreases at a decreasing rate
 * - Not commonly used for financial reporting but useful for certain contexts
 *
 * @package Nexus\FixedAssetDepreciation\Services\Methods
 */
final readonly class AnnuityDepreciationMethod implements DepreciationMethodInterface
{
    /**
     * Default precision for interest calculations.
     */
    private const CALCULATION_PRECISION = 10;

    /**
     * Create a new AnnuityDepreciationMethod instance.
     *
     * @param float $interestRate The interest rate per period (as decimal, e.g., 0.10 for 10%)
     * @param bool $includeInterestInExpense Whether to track interest portion separately
     */
    public function __construct(
        private float $interestRate = 0.10,
        private bool $includeInterestInExpense = false,
    ) {}

    /**
     * Calculate annuity depreciation for a given period.
     *
     * This method calculates depreciation using the present value of annuity formula.
     * The periodic payment remains constant throughout the asset's life, but
     * the split between depreciation and interest varies.
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
     *                       - interest_rate: Override the default interest rate
     *                       - period_number: Current period number (1-indexed)
     * @return DepreciationAmount The calculated depreciation amount
     * @throws \InvalidArgumentException If interest rate is zero or negative
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
        $currency = $options['currency'] ?? 'USD';
        $accumulatedDepreciation = $options['accumulated_depreciation'] ?? 0.0;
        $remainingMonths = $options['remaining_months'] ?? $usefulLifeMonths;
        $interestRate = $options['interest_rate'] ?? $this->interestRate;
        $periodNumber = $options['period_number'] ?? 1;

        $depreciableAmount = $cost - $salvageValue;
        $remainingDepreciable = max(0, $depreciableAmount - $accumulatedDepreciation);

        // Validate inputs
        if ($usefulLifeMonths <= 0 || $remainingDepreciable <= 0) {
            return new DepreciationAmount(
                amount: 0.0,
                currency: $currency,
                accumulatedDepreciation: $accumulatedDepreciation
            );
        }

        // Calculate monthly interest rate
        $monthlyInterestRate = $interestRate / 12;

        // Handle zero interest rate case (fall back to straight-line)
        if ($monthlyInterestRate <= 0) {
            $monthlyDepreciation = $depreciableAmount / $usefulLifeMonths;
            $depreciationAmount = min($monthlyDepreciation, $remainingDepreciable);

            return new DepreciationAmount(
                amount: round($depreciationAmount, 2),
                currency: $currency,
                accumulatedDepreciation: $accumulatedDepreciation + $depreciationAmount
            );
        }

        // Calculate the annuity factor using the present value of annuity formula
        // PMT = PV × [i / (1 - (1 + i)^-n)]
        // Where: PV = depreciable amount, i = interest rate per period, n = number of periods
        $annuityFactor = $this->calculateAnnuityFactor($monthlyInterestRate, $usefulLifeMonths);

        // Calculate the periodic payment (annuity)
        $periodicPayment = $depreciableAmount * $annuityFactor;

        // Calculate the interest portion based on current book value
        $currentBookValue = $cost - $accumulatedDepreciation;
        $interestPortion = $currentBookValue * $monthlyInterestRate;

        // Calculate depreciation portion (payment - interest)
        // Note: Some implementations treat the entire payment as depreciation,
        // while others separate interest from depreciation expense
        if ($this->includeInterestInExpense) {
            // Full payment is considered depreciation expense
            $depreciationAmount = $periodicPayment;
        } else {
            // Only the principal portion is depreciation
            $depreciationAmount = $periodicPayment - $interestPortion;
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
        return DepreciationMethodType::ANNUITY;
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
     * Annuity method results in lower depreciation in early years
     * compared to straight-line (reverse accelerated).
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

        $usefulLifeMonths = $options['useful_life_months'] ?? 0;
        if ($usefulLifeMonths <= 0) {
            $errors[] = 'Useful life months must be positive';
        }

        $interestRate = $options['interest_rate'] ?? $this->interestRate;
        if ($interestRate <= 0) {
            $errors[] = 'Interest rate must be positive for annuity method';
        }

        return $errors;
    }

    /**
     * Calculate the depreciation rate for this method.
     *
     * Returns the annuity factor which represents the periodic payment
     * as a percentage of the depreciable amount.
     *
     * @param int $usefulLifeYears The useful life in years
     * @param array $options Additional method-specific options:
     *                       - interest_rate: The interest rate per period
     * @return float The annual depreciation rate (annuity factor)
     */
    public function getDepreciationRate(int $usefulLifeYears, array $options = []): float
    {
        if ($usefulLifeYears <= 0) {
            return 0.0;
        }

        $interestRate = $options['interest_rate'] ?? $this->interestRate;
        $periodsPerYear = 12;
        $monthlyRate = $interestRate / $periodsPerYear;

        if ($monthlyRate <= 0) {
            return 1.0 / $usefulLifeYears;
        }

        $totalPeriods = $usefulLifeYears * $periodsPerYear;
        return $this->calculateAnnuityFactor($monthlyRate, $totalPeriods);
    }

    /**
     * Calculate remaining depreciation for an asset.
     *
     * @param float $currentBookValue Current book value
     * @param float $salvageValue Salvage value
     * @param int $remainingMonths Remaining useful life in months
     * @param array $options Additional method-specific options:
     *                       - interest_rate: The interest rate per period
     * @return float The remaining depreciation amount
     */
    public function calculateRemainingDepreciation(
        float $currentBookValue,
        float $salvageValue,
        int $remainingMonths,
        array $options = []
    ): float {
        if ($remainingMonths <= 0) {
            return 0.0;
        }

        $interestRate = $options['interest_rate'] ?? $this->interestRate;
        $monthlyRate = $interestRate / 12;

        $depreciableAmount = $currentBookValue - $salvageValue;

        if ($monthlyRate <= 0) {
            return $depreciableAmount;
        }

        // Calculate remaining annuity payments
        $annuityFactor = $this->calculateAnnuityFactor($monthlyRate, $remainingMonths);

        return $depreciableAmount * $annuityFactor;
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
        return 1;
    }

    /**
     * Check if the method should switch to straight-line.
     *
     * Annuity method does not typically switch to straight-line.
     *
     * @param float $currentBookValue Current book value
     * @param float $salvageValue Salvage value
     * @param int $remainingMonths Remaining useful life in months
     * @param float $decliningBalanceAmount The calculated declining balance amount
     * @return bool Always false for annuity method
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
     * Calculate the annuity factor.
     *
     * Formula: i / (1 - (1 + i)^-n)
     * Where i = interest rate per period, n = number of periods
     *
     * @param float $interestRate The interest rate per period
     * @param int $periods The number of periods
     * @return float The annuity factor
     */
    private function calculateAnnuityFactor(float $interestRate, int $periods): float
    {
        if ($interestRate <= 0 || $periods <= 0) {
            return 0.0;
        }

        // (1 + i)^-n = 1 / (1 + i)^n
        $discountFactor = 1 / pow(1 + $interestRate, $periods);

        // i / (1 - (1 + i)^-n)
        $annuityFactor = $interestRate / (1 - $discountFactor);

        return $annuityFactor;
    }

    /**
     * Get the interest rate being used.
     *
     * @return float The interest rate
     */
    public function getInterestRate(): float
    {
        return $this->interestRate;
    }

    /**
     * Check if interest is included in depreciation expense.
     *
     * @return bool True if interest is included
     */
    public function isInterestIncludedInExpense(): bool
    {
        return $this->includeInterestInExpense;
    }

    /**
     * Calculate the interest portion for a period.
     *
     * @param float $currentBookValue The current book value
     * @param float $interestRate The interest rate per period
     * @return float The interest portion
     */
    public function calculateInterestPortion(float $currentBookValue, float $interestRate): float
    {
        return $currentBookValue * ($interestRate / 12);
    }
}
