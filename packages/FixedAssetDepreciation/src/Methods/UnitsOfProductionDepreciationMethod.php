<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Methods;

use DateTimeInterface;
use Nexus\FixedAssetDepreciation\Contracts\DepreciationMethodInterface;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;

/**
 * Units of Production Depreciation Method
 *
 * Usage-based depreciation method where depreciation is calculated based on
 * actual usage (units produced, hours used, miles driven, etc.) rather than time.
 *
 * Formula: 
 * Depreciation per Unit = (Cost - Salvage Value) / Total Expected Units
 * Period Depreciation = Depreciation per Unit × Units Produced in Period
 *
 * Tier: 3 (Enterprise)
 *
 * @package Nexus\FixedAssetDepreciation\Methods
 */
final readonly class UnitsOfProductionDepreciationMethod implements DepreciationMethodInterface
{
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

        if ($totalExpectedUnits <= 0 || $unitsProduced <= 0 || $remainingDepreciable <= 0) {
            return new DepreciationAmount(
                amount: 0.0,
                currency: $currency,
                accumulatedDepreciation: $accumulatedDepreciation
            );
        }

        $depreciationPerUnit = $depreciableAmount / $totalExpectedUnits;
        $depreciationAmount = $depreciationPerUnit * $unitsProduced;
        
        $depreciationAmount = min($depreciationAmount, $remainingDepreciable);

        return new DepreciationAmount(
            amount: round($depreciationAmount, 2),
            currency: $currency,
            accumulatedDepreciation: $accumulatedDepreciation + $depreciationAmount
        );
    }

    public function getType(): DepreciationMethodType
    {
        return DepreciationMethodType::UNITS_OF_PRODUCTION;
    }

    public function supportsProrate(): bool
    {
        return false;
    }

    public function isAccelerated(): bool
    {
        return false;
    }

    public function validate(float $cost, float $salvageValue, array $options): bool
    {
        return count($this->getValidationErrors($cost, $salvageValue, $options)) === 0;
    }

    public function getValidationErrors(float $cost, float $salvageValue, array $options): array
    {
        $errors = [];

        if ($cost <= 0) {
            $errors[] = 'Cost must be positive';
        }

        if ($salvageValue < 0) {
            $errors[] = 'Salvage value cannot be negative';
        }

        $totalExpectedUnits = $options['total_expected_units'] ?? 0;
        if ($totalExpectedUnits <= 0) {
            $errors[] = 'Total expected units must be positive for UOP method';
        }

        return $errors;
    }

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

    public function calculateRemainingDepreciation(
        float $currentBookValue,
        float $salvageValue,
        int $remainingMonths,
        array $options = []
    ): float {
        return max(0, $currentBookValue - $salvageValue);
    }

    public function requiresUnitsData(): bool
    {
        return true;
    }

    public function getMinimumUsefulLifeMonths(): int
    {
        return 0;
    }

    public function shouldSwitchToStraightLine(
        float $currentBookValue,
        float $salvageValue,
        int $remainingMonths,
        float $decliningBalanceAmount
    ): bool {
        return false;
    }
}
