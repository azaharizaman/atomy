<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Methods;

use DateTimeInterface;
use Nexus\FixedAssetDepreciation\Contracts\DepreciationMethodInterface;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;

/**
 * Double Declining Balance Depreciation Method
 *
 * An accelerated depreciation method that doubles the straight-line rate.
 * Results in higher depreciation in early years and lower depreciation later.
 *
 * Formula: Annual Depreciation = Book Value × (2 / Useful Life in Years)
 * Monthly Depreciation = Annual Depreciation / 12
 *
 * Tier: 2 (Advanced)
 *
 * Features:
 * - Automatic switch to straight-line when beneficial
 * - Salvage value is not used in calculation but limits total depreciation
 * - Never depreciates below salvage value
 *
 * @package Nexus\FixedAssetDepreciation\Methods
 */
final readonly class DoubleDecliningDepreciationMethod implements DepreciationMethodInterface
{
    public function __construct(
        private float $decliningFactor = 2.0,
        private bool $switchToStraightLine = true,
    ) {}

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

        if ($usefulLifeYears <= 0 || $remainingDepreciable <= 0 || $currentBookValue <= $salvageValue) {
            return new DepreciationAmount(
                amount: 0.0,
                currency: $currency,
                accumulatedDepreciation: $accumulatedDepreciation
            );
        }

        $annualRate = $this->decliningFactor / $usefulLifeYears;
        $monthlyRate = $annualRate / 12;
        
        $ddbAmount = $currentBookValue * $monthlyRate;
        
        if ($this->switchToStraightLine && $remainingMonths > 0) {
            $slAmount = ($currentBookValue - $salvageValue) / $remainingMonths;
            
            if ($slAmount > $ddbAmount) {
                $depreciationAmount = $slAmount;
            } else {
                $depreciationAmount = $ddbAmount;
            }
        } else {
            $depreciationAmount = $ddbAmount;
        }

        $depreciationAmount = min($depreciationAmount, $remainingDepreciable);
        $depreciationAmount = max(0, $depreciationAmount);

        return new DepreciationAmount(
            amount: round($depreciationAmount, 2),
            currency: $currency,
            accumulatedDepreciation: $accumulatedDepreciation + $depreciationAmount
        );
    }

    public function getType(): DepreciationMethodType
    {
        return DepreciationMethodType::DOUBLE_DECLINING;
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

        $usefulLifeMonths = $options['useful_life_months'] ?? 0;
        if ($usefulLifeMonths <= 0) {
            $errors[] = 'Useful life months must be positive';
        }

        return $errors;
    }

    public function getDepreciationRate(int $usefulLifeYears, array $options = []): float
    {
        if ($usefulLifeYears <= 0) {
            return 0.0;
        }
        return $this->decliningFactor / $usefulLifeYears;
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
    ): bool {
        if ($remainingMonths <= 0) {
            return false;
        }

        $slAmount = ($currentBookValue - $salvageValue) / $remainingMonths;
        return $slAmount > $decliningBalanceAmount;
    }
}
