<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Methods;

use DateTimeInterface;
use Nexus\FixedAssetDepreciation\Contracts\DepreciationMethodInterface;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;

/**
 * Sum-of-Years-Digits Depreciation Method
 *
 * An accelerated depreciation method based on the sum of the years' digits.
 * Results in higher depreciation in early years and lower depreciation later.
 *
 * Formula: 
 * Sum of Years = n × (n + 1) / 2 where n = useful life in years
 * Year N Depreciation = (Cost - Salvage) × (Remaining Life / Sum of Years)
 *
 * Tier: 2 (Advanced)
 *
 * @package Nexus\FixedAssetDepreciation\Methods
 */
final readonly class SumOfYearsDepreciationMethod implements DepreciationMethodInterface
{
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

        if ($usefulLifeYears <= 0 || $remainingDepreciable <= 0) {
            return new DepreciationAmount(
                amount: 0.0,
                currency: $currency,
                accumulatedDepreciation: $accumulatedDepreciation
            );
        }

        $sumOfYears = $this->calculateSumOfYears($usefulLifeYears);
        
        $remainingLife = max(0, $usefulLifeYears - $currentYear + 1);
        
        $yearlyDepreciation = ($depreciableAmount * $remainingLife) / $sumOfYears;
        
        $monthlyDepreciation = $yearlyDepreciation / 12;

        $depreciationAmount = min($monthlyDepreciation, $remainingDepreciable);

        return new DepreciationAmount(
            amount: round($depreciationAmount, 2),
            currency: $currency,
            accumulatedDepreciation: $accumulatedDepreciation + $depreciationAmount
        );
    }

    public function getType(): DepreciationMethodType
    {
        return DepreciationMethodType::SUM_OF_YEARS;
    }

    public function supportsProrate(): bool
    {
        return true;
    }

    public function isAccelerated(): bool
    {
        return true;
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

        if ($salvageValue >= $cost) {
            $errors[] = 'Salvage value must be less than cost';
        }

        $usefulLifeMonths = $options['useful_life_months'] ?? 0;
        if ($usefulLifeMonths < 12) {
            $errors[] = 'Useful life must be at least 12 months for SYD method';
        }

        return $errors;
    }

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
        return false;
    }

    public function getMinimumUsefulLifeMonths(): int
    {
        return 12;
    }

    public function shouldSwitchToStraightLine(
        float $currentBookValue,
        float $salvageValue,
        int $remainingMonths,
        float $decliningBalanceAmount
    ): bool
    {
        return false;
    }

    private function calculateSumOfYears(int $years): int
    {
        return ($years * ($years + 1)) / 2;
    }
}
