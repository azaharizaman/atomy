<?php

declare(strict_types=1);

namespace Nexus\FinancialRatios\Services;

use Nexus\FinancialRatios\Contracts\MarketRatioInterface;
use Nexus\FinancialRatios\Enums\RatioCategory;
use Nexus\FinancialRatios\Exceptions\RatioCalculationException;
use Nexus\FinancialRatios\ValueObjects\RatioResult;

/**
 * Calculator for market ratios.
 */
final readonly class MarketRatioCalculator implements MarketRatioInterface
{
    public function earningsPerShare(float $netIncome, float $outstandingShares): float
    {
        if ($outstandingShares === 0.0) {
            throw RatioCalculationException::divisionByZero('Earnings Per Share', 'outstanding shares');
        }

        return $netIncome / $outstandingShares;
    }

    public function priceToEarnings(float $stockPrice, float $eps): RatioResult
    {
        if ($eps === 0.0) {
            throw RatioCalculationException::divisionByZero('Price to Earnings', 'earnings per share');
        }

        $value = $stockPrice / $eps;

        return new RatioResult(
            ratioName: 'Price to Earnings (P/E) Ratio',
            value: $value,
            category: RatioCategory::MARKET,
            benchmark: 15.0,
            interpretation: $this->interpretPE($value),
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    public function priceToBook(float $stockPrice, float $bookValuePerShare): RatioResult
    {
        if ($bookValuePerShare === 0.0) {
            throw RatioCalculationException::divisionByZero('Price to Book', 'book value per share');
        }

        $value = $stockPrice / $bookValuePerShare;

        return new RatioResult(
            ratioName: 'Price to Book (P/B) Ratio',
            value: $value,
            category: RatioCategory::MARKET,
            benchmark: 1.5,
            interpretation: $this->interpretPB($value),
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    public function priceToSales(float $marketCap, float $revenue): RatioResult
    {
        if ($revenue === 0.0) {
            throw RatioCalculationException::divisionByZero('Price to Sales', 'revenue');
        }

        $value = $marketCap / $revenue;

        return new RatioResult(
            ratioName: 'Price to Sales (P/S) Ratio',
            value: $value,
            category: RatioCategory::MARKET,
            benchmark: 2.0,
            interpretation: $this->interpretPS($value),
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    public function dividendYield(float $annualDividend, float $stockPrice): RatioResult
    {
        if ($stockPrice === 0.0) {
            throw RatioCalculationException::divisionByZero('Dividend Yield', 'stock price');
        }

        $value = $annualDividend / $stockPrice;

        return new RatioResult(
            ratioName: 'Dividend Yield',
            value: $value,
            category: RatioCategory::MARKET,
            benchmark: 0.03,
            interpretation: $this->interpretDividendYield($value),
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    public function dividendPayoutRatio(float $dividends, float $netIncome): RatioResult
    {
        if ($netIncome === 0.0) {
            throw RatioCalculationException::divisionByZero('Dividend Payout Ratio', 'net income');
        }

        $value = $dividends / $netIncome;

        return new RatioResult(
            ratioName: 'Dividend Payout Ratio',
            value: $value,
            category: RatioCategory::MARKET,
            benchmark: 0.40,
            interpretation: $this->interpretPayoutRatio($value),
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    public function bookValuePerShare(float $totalEquity, float $outstandingShares): float
    {
        if ($outstandingShares === 0.0) {
            throw RatioCalculationException::divisionByZero('Book Value Per Share', 'outstanding shares');
        }

        return $totalEquity / $outstandingShares;
    }

    public function marketCapitalization(float $stockPrice, float $outstandingShares): float
    {
        return $stockPrice * $outstandingShares;
    }

    private function interpretPE(float $value): string
    {
        if ($value < 0) {
            return 'Negative P/E - company is losing money';
        }

        if ($value < 10) {
            return 'Low P/E - potentially undervalued or low growth expectations';
        }

        if ($value <= 20) {
            return 'Moderate P/E - fairly valued';
        }

        if ($value <= 40) {
            return 'High P/E - high growth expectations priced in';
        }

        return 'Very high P/E - may be overvalued or speculative';
    }

    private function interpretPB(float $value): string
    {
        if ($value < 1.0) {
            return 'Trading below book value - potentially undervalued';
        }

        if ($value <= 2.0) {
            return 'Reasonable price relative to book value';
        }

        return 'Premium to book value - high growth or intangible assets';
    }

    private function interpretPS(float $value): string
    {
        if ($value < 1.0) {
            return 'Low P/S - potentially undervalued';
        }

        if ($value <= 3.0) {
            return 'Moderate P/S valuation';
        }

        return 'High P/S - high growth expectations';
    }

    private function interpretDividendYield(float $value): string
    {
        if ($value === 0.0) {
            return 'No dividend - growth stock';
        }

        if ($value < 0.02) {
            return 'Low yield';
        }

        if ($value <= 0.04) {
            return 'Moderate dividend yield';
        }

        if ($value <= 0.06) {
            return 'High dividend yield';
        }

        return 'Very high yield - verify sustainability';
    }

    private function interpretPayoutRatio(float $value): string
    {
        if ($value < 0.30) {
            return 'Low payout - room for dividend growth';
        }

        if ($value <= 0.60) {
            return 'Sustainable payout ratio';
        }

        if ($value <= 0.80) {
            return 'High payout - limited room for growth';
        }

        return 'Very high payout - dividend may not be sustainable';
    }
}
