<?php

declare(strict_types=1);

namespace Nexus\FinancialRatios\Contracts;

use Nexus\FinancialRatios\ValueObjects\RatioResult;

/**
 * Contract for market ratio calculations.
 */
interface MarketRatioInterface
{
    /**
     * Calculate earnings per share (Net Income / Outstanding Shares).
     */
    public function earningsPerShare(float $netIncome, float $outstandingShares): float;

    /**
     * Calculate price to earnings ratio (Stock Price / EPS).
     */
    public function priceToEarnings(float $stockPrice, float $eps): RatioResult;

    /**
     * Calculate price to book ratio (Stock Price / Book Value per Share).
     */
    public function priceToBook(float $stockPrice, float $bookValuePerShare): RatioResult;

    /**
     * Calculate price to sales ratio (Market Cap / Revenue).
     */
    public function priceToSales(float $marketCap, float $revenue): RatioResult;

    /**
     * Calculate dividend yield (Annual Dividend / Stock Price).
     */
    public function dividendYield(float $annualDividend, float $stockPrice): RatioResult;

    /**
     * Calculate dividend payout ratio (Dividends / Net Income).
     */
    public function dividendPayoutRatio(float $dividends, float $netIncome): RatioResult;

    /**
     * Calculate book value per share (Total Equity / Outstanding Shares).
     */
    public function bookValuePerShare(float $totalEquity, float $outstandingShares): float;

    /**
     * Calculate market capitalization (Stock Price * Outstanding Shares).
     */
    public function marketCapitalization(float $stockPrice, float $outstandingShares): float;
}
