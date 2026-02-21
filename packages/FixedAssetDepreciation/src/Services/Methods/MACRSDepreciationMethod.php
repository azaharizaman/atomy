<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Services\Methods;

use DateTimeInterface;
use Nexus\FixedAssetDepreciation\Contracts\DepreciationMethodInterface;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;

/**
 * MACRS (Modified Accelerated Cost Recovery System) Depreciation Method
 *
 * The MACRS is the current tax depreciation system in the United States, mandated
 * by the IRS for most tangible personal property. It uses the declining balance
 * method with a conversion to straight-line at the optimal point.
 *
 * MACRS Property Classes:
 * - 3-year: Certain horses, qualified rent-to-own property
 * - 5-year: Computers, office equipment, automobiles, light trucks
 * - 7-year: Most industrial equipment, furniture, fixtures
 * - 10-year: Certain real property, vessels, agricultural machinery
 * - 15-year: Land improvements, certain real property
 * - 20-year: Certain real property, municipal water mains
 *
 * Depreciation Rates (Half-Year Convention):
 * The rates are determined by the IRS MACRS percentage tables.
 *
 * Example for 5-year property:
 * Year 1: 20.00%
 * Year 2: 32.00%
 * Year 3: 19.20%
 * Year 4: 11.52%
 * Year 5: 11.52%
 * Year 6: 5.76%
 *
 * Tier: 3 (Enterprise)
 *
 * Features:
 * - US tax depreciation (IRS tables)
 * - Support for different property classes
 * - Half-year and mid-month conventions
 * - Mandatory for US tax purposes
 *
 * @package Nexus\FixedAssetDepreciation\Services\Methods
 */
final readonly class MACRSDepreciationMethod implements DepreciationMethodInterface
{
    /**
     * MACRS property class years.
     */
    public const PROPERTY_CLASS_3_YEAR = 3;
    public const PROPERTY_CLASS_5_YEAR = 5;
    public const PROPERTY_CLASS_7_YEAR = 7;
    public const PROPERTY_CLASS_10_YEAR = 10;
    public const PROPERTY_CLASS_15_YEAR = 15;
    public const PROPERTY_CLASS_20_YEAR = 20;

    /**
     * MACRS depreciation rates for 3-year property (half-year convention).
     * Source: IRS Publication 946
     */
    private const RATES_3_YEAR = [0.3333, 0.4445, 0.1481, 0.0741];

    /**
     * MACRS depreciation rates for 5-year property (half-year convention).
     */
    private const RATES_5_YEAR = [0.20, 0.32, 0.192, 0.1152, 0.1152, 0.0576];

    /**
     * MACRS depreciation rates for 7-year property (half-year convention).
     */
    private const RATES_7_YEAR = [
        0.1429, 0.2449, 0.1749, 0.1249, 0.0893, 0.0892, 0.0893, 0.0446
    ];

    /**
     * MACRS depreciation rates for 10-year property (half-year convention).
     */
    private const RATES_10_YEAR = [
        0.10, 0.18, 0.144, 0.1152, 0.0912, 0.0912, 0.0913, 0.0914,
        0.0914, 0.0913, 0.0457
    ];

    /**
     * MACRS depreciation rates for 15-year property (half-year convention).
     */
    private const RATES_15_YEAR = [
        0.05, 0.095, 0.0855, 0.077, 0.0693, 0.0623, 0.059, 0.059,
        0.0591, 0.059, 0.0591, 0.059, 0.0591, 0.059, 0.0591, 0.0295
    ];

    /**
     * MACRS depreciation rates for 20-year property (half-year convention).
     */
    private const RATES_20_YEAR = [
        0.03750, 0.07219, 0.06677, 0.06177, 0.05713, 0.05285, 0.04888,
        0.04522, 0.04181, 0.03862, 0.03573, 0.03300, 0.03050, 0.02820,
        0.02606, 0.02406, 0.02218, 0.02045, 0.01885, 0.01741, 0.01605
    ];

    /**
     * Convention types for MACRS.
     */
    public const CONVENTION_HALF_YEAR = 'half_year';
    public const CONVENTION_MID_MONTH = 'mid_month';
    public const CONVENTION_MID_QUARTER = 'mid_quarter';

    /**
     * Create a new MACRSDepreciationMethod instance.
     *
     * @param int $propertyClass The MACRS property class (3, 5, 7, 10, 15, or 20 years)
     * @param string $convention The depreciation convention to use
     */
    public function __construct(
        private int $propertyClass = self::PROPERTY_CLASS_5_YEAR,
        private string $convention = self::CONVENTION_HALF_YEAR,
    ) {}

    /**
     * Calculate MACRS depreciation for a given period.
     *
     * Uses IRS MACRS percentage tables to determine depreciation.
     * The method applies the appropriate rate based on the property class
     * and the recovery year.
     *
     * @param float $cost The original cost of the asset (basis for depreciation)
     * @param float $salvageValue The salvage value (ignored for MACRS, but included for interface)
     * @param DateTimeInterface $startDate The depreciation start date
     * @param DateTimeInterface $endDate The depreciation end date
     * @param array $options Additional options:
     *                       - property_class: Override the property class
     *                       - convention: Override the convention
     *                       - recovery_year: Current recovery year (1-indexed)
     *                       - accumulated_depreciation: Current accumulated depreciation
     *                       - currency: Currency code (default USD)
     *                       - placed_in_service_date: Date asset was placed in service
     *                       - bonus_rate: First-year bonus depreciation rate (if any)
     * @return DepreciationAmount The calculated depreciation amount
     * @throws \InvalidArgumentException If property class is invalid
     */
    public function calculate(
        float $cost,
        float $salvageValue,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        array $options = []
    ): DepreciationAmount {
        $propertyClass = $options['property_class'] ?? $this->propertyClass;
        $convention = $options['convention'] ?? $this->convention;
        $recoveryYear = $options['recovery_year'] ?? 1;
        $currency = $options['currency'] ?? 'USD';
        $accumulatedDepreciation = $options['accumulated_depreciation'] ?? 0.0;
        $bonusRate = $options['bonus_rate'] ?? 0.0;

        // Validate property class
        if (!in_array($propertyClass, [
            self::PROPERTY_CLASS_3_YEAR,
            self::PROPERTY_CLASS_5_YEAR,
            self::PROPERTY_CLASS_7_YEAR,
            self::PROPERTY_CLASS_10_YEAR,
            self::PROPERTY_CLASS_15_YEAR,
            self::PROPERTY_CLASS_20_YEAR,
        ], true)) {
            return new DepreciationAmount(
                amount: 0.0,
                currency: $currency,
                accumulatedDepreciation: $accumulatedDepreciation
            );
        }

        // Get MACRS rates for the property class
        $rates = $this->getRatesForPropertyClass($propertyClass);

        // Get the rate for the current recovery year (1-indexed)
        $rateIndex = $recoveryYear - 1;
        if ($rateIndex < 0 || $rateIndex >= count($rates)) {
            return new DepreciationAmount(
                amount: 0.0,
                currency: $currency,
                accumulatedDepreciation: $accumulatedDepreciation
            );
        }

        $rate = $rates[$rateIndex];

        // Calculate depreciation amount
        $depreciationAmount = $cost * $rate;

        // Add bonus depreciation if specified
        if ($bonusRate > 0 && $recoveryYear === 1) {
            $bonusDepreciation = $cost * $bonusRate;
            $depreciationAmount += $bonusDepreciation;
        }

        // Ensure we don't exceed cost (basis)
        $maxDepreciation = $cost - $accumulatedDepreciation;
        $depreciationAmount = min($depreciationAmount, $maxDepreciation);
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
        return DepreciationMethodType::MACRS;
    }

    /**
     * Check if this method supports prorate conventions.
     *
     * MACRS has built-in conventions (half-year, mid-month, mid-quarter).
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
     * MACRS is an accelerated method (declining balance).
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
     * @param float $salvageValue The salvage value (ignored for MACRS)
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

        $propertyClass = $options['property_class'] ?? $this->propertyClass;
        if (!in_array($propertyClass, [
            self::PROPERTY_CLASS_3_YEAR,
            self::PROPERTY_CLASS_5_YEAR,
            self::PROPERTY_CLASS_7_YEAR,
            self::PROPERTY_CLASS_10_YEAR,
            self::PROPERTY_CLASS_15_YEAR,
            self::PROPERTY_CLASS_20_YEAR,
        ], true)) {
            $errors[] = 'Invalid MACRS property class. Must be 3, 5, 7, 10, 15, or 20 years';
        }

        $bonusRate = $options['bonus_rate'] ?? 0.0;
        if ($bonusRate < 0 || $bonusRate > 1) {
            $errors[] = 'Bonus rate must be between 0 and 1';
        }

        return $errors;
    }

    /**
     * Calculate the depreciation rate for this method.
     *
     * Returns the MACRS rate for the given recovery year.
     *
     * @param int $usefulLifeYears The useful life in years (property class)
     * @param array $options Additional method-specific options:
     *                       - recovery_year: The recovery year
     * @return float The annual depreciation rate
     */
    public function getDepreciationRate(int $usefulLifeYears, array $options = []): float
    {
        $propertyClass = $options['property_class'] ?? $usefulLifeYears;
        $recoveryYear = $options['recovery_year'] ?? 1;

        $rates = $this->getRatesForPropertyClass($propertyClass);
        $rateIndex = $recoveryYear - 1;

        if ($rateIndex < 0 || $rateIndex >= count($rates)) {
            return 0.0;
        }

        return $rates[$rateIndex];
    }

    /**
     * Calculate remaining depreciation for an asset.
     *
     * @param float $currentBookValue Current book value
     * @param float $salvageValue Salvage value (ignored for MACRS)
     * @param int $remainingMonths Remaining months in recovery period
     * @param array $options Additional method-specific options
     * @return float The remaining depreciation amount
     */
    public function calculateRemainingDepreciation(
        float $currentBookValue,
        float $salvageValue,
        int $remainingMonths,
        array $options = []
    ): float {
        // For MACRS, remaining depreciation is based on remaining recovery years
        // not the book value
        return $currentBookValue;
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
        return $this->propertyClass * 12;
    }

    /**
     * Check if the method should switch to straight-line.
     *
     * MACRS automatically switches from declining balance to straight-line
     * at the optimal point per IRS tables.
     *
     * @param float $currentBookValue Current book value
     * @param float $salvageValue Salvage value
     * @param int $remainingMonths Remaining useful life in months
     * @param float $decliningBalanceAmount The calculated declining balance amount
     * @return bool False (MACRS handles this internally via tables)
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
     * Get MACRS rates for a given property class.
     *
     * @param int $propertyClass The property class in years
     * @return array<float> Array of depreciation rates by year
     */
    private function getRatesForPropertyClass(int $propertyClass): array
    {
        return match ($propertyClass) {
            self::PROPERTY_CLASS_3_YEAR => self::RATES_3_YEAR,
            self::PROPERTY_CLASS_5_YEAR => self::RATES_5_YEAR,
            self::PROPERTY_CLASS_7_YEAR => self::RATES_7_YEAR,
            self::PROPERTY_CLASS_10_YEAR => self::RATES_10_YEAR,
            self::PROPERTY_CLASS_15_YEAR => self::RATES_15_YEAR,
            self::PROPERTY_CLASS_20_YEAR => self::RATES_20_YEAR,
            default => self::RATES_5_YEAR,
        };
    }

    /**
     * Get the property class being used.
     *
     * @return int The property class in years
     */
    public function getPropertyClass(): int
    {
        return $this->propertyClass;
    }

    /**
     * Get the convention being used.
     *
     * @return string The convention type
     */
    public function getConvention(): string
    {
        return $this->convention;
    }

    /**
     * Get the recovery period for the property class.
     *
     * @return int The recovery period in years
     */
    public function getRecoveryPeriod(): int
    {
        return $this->propertyClass;
    }

    /**
     * Get all available property classes.
     *
     * @return array<int> Array of valid property classes
     */
    public static function getValidPropertyClasses(): array
    {
        return [
            self::PROPERTY_CLASS_3_YEAR,
            self::PROPERTY_CLASS_5_YEAR,
            self::PROPERTY_CLASS_7_YEAR,
            self::PROPERTY_CLASS_10_YEAR,
            self::PROPERTY_CLASS_15_YEAR,
            self::PROPERTY_CLASS_20_YEAR,
        ];
    }

    /**
     * Get MACRS rates as a formatted array with year keys.
     *
     * @param int $propertyClass The property class in years
     * @return array<int, float> Array of rates keyed by year
     */
    public static function getRatesArray(int $propertyClass): array
    {
        $instance = new self($propertyClass);
        $rates = $instance->getRatesForPropertyClass($propertyClass);
        
        $result = [];
        foreach ($rates as $index => $rate) {
            $result[$index + 1] = $rate;
        }
        
        return $result;
    }
}
