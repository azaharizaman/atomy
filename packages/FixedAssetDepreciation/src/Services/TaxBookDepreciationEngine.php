<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Services;

use DateTimeInterface;
use Nexus\FixedAssetDepreciation\Contracts\DepreciationMethodInterface;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;

/**
 * Tax-Book Depreciation Engine
 *
 * This engine calculates parallel book (financial reporting) and tax depreciation
 * to support deferred tax calculations and financial statement preparation.
 *
 * Key Concepts:
 * - Book Depreciation: GAAP-compliant depreciation for financial statements
 * - Tax Depreciation: IRS-compliant depreciation (MACRS) for tax returns
 * - Temporary Difference: Difference between book and tax basis that reverses over time
 * - Deferred Tax Liability: Tax payable in future periods due to temporary differences
 *
 * Example:
 * - Asset Cost: $100,000
 * - Book Method: Straight-line, 5 years, $20,000/year
 * - Tax Method: MACRS 5-year, Year 1 = $20,000
 * - Year 1:
 *   - Book Depreciation: $20,000
 *   - Tax Depreciation: $20,000
 *   - Temporary Difference: $0
 * - Year 2:
 *   - Book Depreciation: $20,000
 *   - Tax Depreciation: $32,000
 *   - Temporary Difference: -$12,000 (tax > book)
 *
 * Tier: 3 (Enterprise)
 *
 * Features:
 * - Parallel book and tax depreciation calculation
 * - Calculate temporary differences
 * - Support for deferred tax liability
 * - Multiple depreciation method combinations
 * - Comprehensive tax book reporting
 *
 * @package Nexus\FixedAssetDepreciation\Services
 */
final readonly class TaxBookDepreciationEngine
{
    /**
     * Default tax rate for deferred tax calculations.
     */
    private const DEFAULT_TAX_RATE = 0.21;

    /**
     * Create a new TaxBookDepreciationEngine instance.
     *
     * @param float $taxRate The corporate tax rate for deferred tax calculations
     */
    public function __construct(
        private float $taxRate = self::DEFAULT_TAX_RATE,
    ) {}

    /**
     * Calculate book and tax depreciation for a period.
     *
     * Runs both book and tax depreciation calculations and computes
     * the temporary difference and deferred tax implications.
     *
     * @param float $cost The original cost of the asset
     * @param float $salvageValue The salvage value
     * @param DepreciationMethodInterface $bookMethod The book depreciation method
     * @param DepreciationMethodInterface $taxMethod The tax depreciation method
     * @param DateTimeInterface $startDate The depreciation start date
     * @param DateTimeInterface $endDate The depreciation end date
     * @param array $bookOptions Options for book depreciation calculation
     * @param array $taxOptions Options for tax depreciation calculation
     * @return TaxBookDepreciationResult The combined result with book, tax, and deferred amounts
     */
    public function calculate(
        float $cost,
        float $salvageValue,
        DepreciationMethodInterface $bookMethod,
        DepreciationMethodInterface $taxMethod,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        array $bookOptions = [],
        array $taxOptions = []
    ): TaxBookDepreciationResult {
        // Calculate book depreciation
        $bookDepreciation = $bookMethod->calculate(
            $cost,
            $salvageValue,
            $startDate,
            $endDate,
            $bookOptions
        );

        // Calculate tax depreciation
        $taxDepreciation = $taxMethod->calculate(
            $cost,
            $salvageValue,
            $startDate,
            $endDate,
            $taxOptions
        );

        // Calculate temporary difference (tax - book)
        // Positive = tax depreciation > book depreciation (tax liability deferred)
        // Negative = book depreciation > tax depreciation (tax asset created)
        $temporaryDifference = $taxDepreciation->amount - $bookDepreciation->amount;

        // Calculate deferred tax impact
        $deferredTaxLiability = $temporaryDifference * $this->taxRate;

        return new TaxBookDepreciationResult(
            bookDepreciation: $bookDepreciation,
            taxDepreciation: $taxDepreciation,
            temporaryDifference: $temporaryDifference,
            deferredTaxLiability: $deferredTaxLiability,
            taxRate: $this->taxRate,
        );
    }

    /**
     * Calculate full depreciation schedule with book/tax parallel tracking.
     *
     * Generates a complete schedule showing book and tax depreciation
     * for each period along with cumulative temporary differences.
     *
     * @param float $cost The original cost of the asset
     * @param float $salvageValue The salvage value
     * @param DepreciationMethodInterface $bookMethod The book depreciation method
     * @param DepreciationMethodInterface $taxMethod The tax depreciation method
     * @param DateTimeInterface $startDate The depreciation start date
     * @param int $totalPeriods Total number of periods to calculate
     * @param array $baseBookOptions Base options for book depreciation
     * @param array $baseTaxOptions Base options for tax depreciation
     * @return array<TaxBookDepreciationResult> Array of results for each period
     */
    public function calculateSchedule(
        float $cost,
        float $salvageValue,
        DepreciationMethodInterface $bookMethod,
        DepreciationMethodInterface $taxMethod,
        DateTimeInterface $startDate,
        int $totalPeriods,
        array $baseBookOptions = [],
        array $baseTaxOptions = []
    ): array {
        $results = [];
        $accumulatedBookDepreciation = 0.0;
        $accumulatedTaxDepreciation = 0.0;
        $cumulativeTemporaryDifference = 0.0;

        for ($period = 1; $period <= $totalPeriods; $period++) {
            // Calculate period dates
            $periodStartDate = clone $startDate;
            $periodStartDate->modify("+{$period} months");

            $periodEndDate = clone $startDate;
            $periodEndDate->modify('+' . ($period) . ' months');

            // Prepare options for this period
            $bookOptions = array_merge($baseBookOptions, [
                'accumulated_depreciation' => $accumulatedBookDepreciation,
                'period_number' => $period,
                'remaining_months' => max(0, $totalPeriods - $period + 1),
            ]);

            $taxOptions = array_merge($baseTaxOptions, [
                'accumulated_depreciation' => $accumulatedTaxDepreciation,
                'recovery_year' => $period,
            ]);

            // Calculate depreciation for this period
            $result = $this->calculate(
                $cost,
                $salvageValue,
                $bookMethod,
                $taxMethod,
                $periodStartDate,
                $periodEndDate,
                $bookOptions,
                $taxOptions
            );

            // Update accumulated values
            $accumulatedBookDepreciation = $result->bookDepreciation->accumulatedDepreciation ?? $accumulatedBookDepreciation;
            $accumulatedTaxDepreciation = $result->taxDepreciation->accumulatedDepreciation ?? $accumulatedTaxDepreciation;
            $cumulativeTemporaryDifference += $result->temporaryDifference;

            // Add cumulative values to result
            $result = new TaxBookDepreciationResult(
                bookDepreciation: $result->bookDepreciation,
                taxDepreciation: $result->taxDepreciation,
                temporaryDifference: $result->temporaryDifference,
                deferredTaxLiability: $result->deferredTaxLiability,
                taxRate: $this->taxRate,
                accumulatedBookDepreciation: $accumulatedBookDepreciation,
                accumulatedTaxDepreciation: $accumulatedTaxDepreciation,
                cumulativeTemporaryDifference: $cumulativeTemporaryDifference,
            );

            $results[] = $result;
        }

        return $results;
    }

    /**
     * Calculate deferred tax liability for an asset.
     *
     * The deferred tax liability represents the future tax cost that arises
     * when book depreciation is less than tax depreciation.
     *
     * @param float $cumulativeTemporaryDifference Cumulative temporary difference
     * @param float|null $taxRate Optional override for tax rate
     * @return float The deferred tax liability
     */
    public function calculateDeferredTaxLiability(
        float $cumulativeTemporaryDifference,
        ?float $taxRate = null
    ): float {
        $rate = $taxRate ?? $this->taxRate;
        return $cumulativeTemporaryDifference * $rate;
    }

    /**
     * Calculate deferred tax asset for an asset.
     *
     * A deferred tax asset arises when book depreciation exceeds tax depreciation,
     * creating a future tax benefit.
     *
     * @param float $cumulativeTemporaryDifference Cumulative temporary difference (should be negative)
     * @param float|null $taxRate Optional override for tax rate
     * @return float The deferred tax asset (positive value)
     */
    public function calculateDeferredTaxAsset(
        float $cumulativeTemporaryDifference,
        ?float $taxRate = null
    ): float {
        // Return positive value representing the tax benefit
        return abs($cumulativeTemporaryDifference * ($taxRate ?? $this->taxRate));
    }

    /**
     * Get the tax rate being used.
     *
     * @return float The tax rate
     */
    public function getTaxRate(): float
    {
        return $this->taxRate;
    }

    /**
     * Calculate the book basis of an asset.
     *
     * The book basis is cost minus accumulated book depreciation.
     *
     * @param float $cost The original cost
     * @param float $accumulatedBookDepreciation Accumulated book depreciation
     * @return float The book basis (net book value)
     */
    public function calculateBookBasis(float $cost, float $accumulatedBookDepreciation): float
    {
        return max(0, $cost - $accumulatedBookDepreciation);
    }

    /**
     * Calculate the tax basis of an asset.
     *
     * The tax basis is cost minus accumulated tax depreciation.
     *
     * @param float $cost The original cost
     * @param float $accumulatedTaxDepreciation Accumulated tax depreciation
     * @return float The tax basis
     */
    public function calculateTaxBasis(float $cost, float $accumulatedTaxDepreciation): float
    {
        return max(0, $cost - $accumulatedTaxDepreciation);
    }

    /**
     * Calculate the basis difference between book and tax.
     *
     * @param float $bookBasis The book basis
     * @param float $taxBasis The tax basis
     * @return float The basis difference
     */
    public function calculateBasisDifference(float $bookBasis, float $taxBasis): float
    {
        return $bookBasis - $taxBasis;
    }

    /**
     * Check if there's a net deferred tax liability.
     *
     * @param float $cumulativeTemporaryDifference The cumulative temporary difference
     * @return bool True if there's a deferred tax liability
     */
    public function hasDeferredTaxLiability(float $cumulativeTemporaryDifference): bool
    {
        return $cumulativeTemporaryDifference > 0;
    }

    /**
     * Check if there's a net deferred tax asset.
     *
     * @param float $cumulativeTemporaryDifference The cumulative temporary difference
     * @return bool True if there's a deferred tax asset
     */
    public function hasDeferredTaxAsset(float $cumulativeTemporaryDifference): bool
    {
        return $cumulativeTemporaryDifference < 0;
    }

    /**
     * Get common book/tax method combinations.
     *
     * @return array<string, array{book: DepreciationMethodType, tax: DepreciationMethodType}>
     */
    public static function getCommonCombinations(): array
    {
        return [
            'sl_to_macrs_5' => [
                'book' => DepreciationMethodType::STRAIGHT_LINE,
                'tax' => DepreciationMethodType::MACRS,
            ],
            'sl_to_macrs_7' => [
                'book' => DepreciationMethodType::STRAIGHT_LINE,
                'tax' => DepreciationMethodType::MACRS,
            ],
            'ddb_to_macrs_5' => [
                'book' => DepreciationMethodType::DOUBLE_DECLINING,
                'tax' => DepreciationMethodType::MACRS,
            ],
            'ddb_to_macrs_7' => [
                'book' => DepreciationMethodType::DOUBLE_DECLINING,
                'tax' => DepreciationMethodType::MACRS,
            ],
        ];
    }
}

/**
 * Result object for Tax-Book Depreciation calculations.
 *
 * @package Nexus\FixedAssetDepreciation\Services
 */
final class TaxBookDepreciationResult
{
    public function __construct(
        public readonly DepreciationAmount $bookDepreciation,
        public readonly DepreciationAmount $taxDepreciation,
        public readonly float $temporaryDifference,
        public readonly float $deferredTaxLiability,
        public readonly float $taxRate,
        public readonly ?float $accumulatedBookDepreciation = null,
        public readonly ?float $accumulatedTaxDepreciation = null,
        public readonly ?float $cumulativeTemporaryDifference = null,
    ) {}

    /**
     * Get the book depreciation amount.
     *
     * @return float
     */
    public function getBookDepreciationAmount(): float
    {
        return $this->bookDepreciation->amount;
    }

    /**
     * Get the tax depreciation amount.
     *
     * @return float
     */
    public function getTaxDepreciationAmount(): float
    {
        return $this->taxDepreciation->amount;
    }

    /**
     * Get the net book value after this period's depreciation.
     *
     * @param float $originalCost The original asset cost
     * @return float
     */
    public function getNetBookValue(float $originalCost): float
    {
        $accumulated = $this->accumulatedBookDepreciation ?? $this->bookDepreciation->accumulatedDepreciation ?? 0;
        return max(0, $originalCost - $accumulated);
    }

    /**
     * Get the tax basis after this period's depreciation.
     *
     * @param float $originalCost The original asset cost
     * @return float
     */
    public function getTaxBasis(float $originalCost): float
    {
        $accumulated = $this->accumulatedTaxDepreciation ?? $this->taxDepreciation->accumulatedDepreciation ?? 0;
        return max(0, $originalCost - $accumulated);
    }

    /**
     * Check if this period creates a tax timing advantage.
     *
     * @return bool
     */
    public function hasTaxTimingAdvantage(): bool
    {
        return $this->temporaryDifference > 0;
    }

    /**
     * Check if this period creates a tax timing disadvantage.
     *
     * @return bool
     */
    public function hasTaxTimingDisadvantage(): bool
    {
        return $this->temporaryDifference < 0;
    }

    /**
     * Format the result as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'book_depreciation' => $this->bookDepreciation->amount,
            'book_accumulated' => $this->accumulatedBookDepreciation ?? $this->bookDepreciation->accumulatedDepreciation ?? 0,
            'tax_depreciation' => $this->taxDepreciation->amount,
            'tax_accumulated' => $this->accumulatedTaxDepreciation ?? $this->taxDepreciation->accumulatedDepreciation ?? 0,
            'temporary_difference' => $this->temporaryDifference,
            'cumulative_temporary_difference' => $this->cumulativeTemporaryDifference,
            'deferred_tax_liability' => $this->deferredTaxLiability,
            'tax_rate' => $this->taxRate,
        ];
    }
}
