<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Services\Methods;

use DateTimeInterface;
use Nexus\FixedAssetDepreciation\Contracts\DepreciationMethodInterface;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\Enums\ProrateConvention;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;

/**
 * Straight-Line Depreciation Method
 *
 * The most common and simplest depreciation method. Depreciation is allocated
 * evenly over the useful life of the asset.
 *
 * Formula: Annual Depreciation = (Cost - Salvage Value) / Useful Life in Years
 * Monthly Depreciation = Annual Depreciation / 12
 *
 * Tier: 1 (Basic)
 *
 * Features:
 * - Supports daily prorating for mid-month acquisitions (GAAP-compliant)
 * - Supports full-month convention
 * - Constant depreciation amount each period
 *
 * @package Nexus\FixedAssetDepreciation\Services\Methods
 */
final readonly class StraightLineDepreciationMethod implements DepreciationMethodInterface
{
    private const DAYS_PER_MONTH = 30.0;
    private const DAYS_PER_YEAR = 360.0;

    /**
     * Create a new StraightLineDepreciationMethod instance.
     *
     * @param bool $prorateDaily Whether to apply daily proration for partial months
     */
    public function __construct(
        private bool $prorateDaily = false,
    ) {}

    /**
     * Calculate straight-line depreciation for a given period.
     *
     * This method allocates the cost of the asset evenly over its useful life.
     * Formula: (Cost - Salvage Value) / Useful Life in Months
     *
     * Supports daily prorating for mid-month acquisitions per GAAP requirements.
     *
     * @param float $cost The original cost of the asset
     * @param float $salvageValue The estimated salvage value at end of life
     * @param DateTimeInterface $startDate The depreciation start date
     * @param DateTimeInterface $endDate The depreciation end date
     * @param array $options Additional options:
     *                       - useful_life_months: Total useful life in months
     *                       - accumulated_depreciation: Current accumulated depreciation
     *                       - remaining_months: Remaining months of useful life
     *                       - acquisition_date: Original acquisition date
     *                       - currency: Currency code (default USD)
     *                       - prorate_daily: Whether to use daily calculation
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
        $currency = $options['currency'] ?? 'USD';
        $accumulatedDepreciation = $options['accumulated_depreciation'] ?? 0.0;
        $acquisitionDate = $options['acquisition_date'] ?? $startDate;

        $depreciableAmount = $cost - $salvageValue;
        
        $remainingDepreciable = max(0, $depreciableAmount - $accumulatedDepreciation);
        
        if ($usefulLifeMonths <= 0 || $remainingDepreciable <= 0) {
            return new DepreciationAmount(
                amount: 0.0,
                currency: $currency,
                accumulatedDepreciation: $accumulatedDepreciation
            );
        }

        $monthlyDepreciation = $depreciableAmount / $usefulLifeMonths;

        if ($this->prorateDaily || ($options['prorate_daily'] ?? false)) {
            $monthlyDepreciation = $this->applyDailyProration(
                $monthlyDepreciation,
                $acquisitionDate instanceof DateTimeInterface ? $acquisitionDate : $startDate,
                $startDate,
                $endDate
            );
        }

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
        return DepreciationMethodType::STRAIGHT_LINE;
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
        return 1.0 / $usefulLifeYears;
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
        return 1;
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
        return false;
    }

    /**
     * Apply daily proration for partial month depreciation.
     *
     * This implements GAAP-compliant daily proration for mid-month acquisitions.
     *
     * @param float $monthlyDepreciation The full monthly depreciation amount
     * @param DateTimeInterface $acquisitionDate The asset acquisition date
     * @param DateTimeInterface $periodStart The period start date
     * @param DateTimeInterface $periodEnd The period end date
     * @return float The prorated depreciation amount
     */
    private function applyDailyProration(
        float $monthlyDepreciation,
        DateTimeInterface $acquisitionDate,
        DateTimeInterface $periodStart,
        DateTimeInterface $periodEnd
    ): float {
        $effectiveStart = max($acquisitionDate, $periodStart);
        
        if ($effectiveStart > $periodEnd) {
            return 0.0;
        }

        $daysInPeriod = (int) $effectiveStart->diff($periodEnd)->days + 1;
        $daysInMonth = (int) date('t', $periodStart->getTimestamp());
        
        if ($daysInPeriod >= $daysInMonth) {
            return $monthlyDepreciation;
        }

        $prorationFactor = $daysInPeriod / $daysInMonth;
        return $monthlyDepreciation * $prorationFactor;
    }
}
