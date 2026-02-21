<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Methods;

use DateTimeInterface;
use Nexus\FixedAssetDepreciation\Contracts\DepreciationMethodInterface;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
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
 * @package Nexus\FixedAssetDepreciation\Methods
 */
final readonly class StraightLineDepreciationMethod implements DepreciationMethodInterface
{
    private const DAYS_PER_MONTH = 30.0;
    private const DAYS_PER_YEAR = 360.0;

    public function __construct(
        private bool $prorateDaily = false,
    ) {}

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

        if ($this->prorateDaily) {
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

    public function getType(): DepreciationMethodType
    {
        return DepreciationMethodType::STRAIGHT_LINE;
    }

    public function supportsProrate(): bool
    {
        return true;
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

        if ($salvageValue >= $cost) {
            $errors[] = 'Salvage value must be less than cost';
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
        return 1.0 / $usefulLifeYears;
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
        return 1;
    }

    public function shouldSwitchToStraightLine(
        float $currentBookValue,
        float $salvageValue,
        int $remainingMonths,
        float $decliningBalanceAmount
    ): bool {
        return false;
    }

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
