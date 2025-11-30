<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\Contracts;

/**
 * Pricing analytics repository interface for anomaly detection
 * 
 * Provides pricing intelligence queries for market comparison,
 * competitive analysis, and contract compliance validation.
 */
interface PricingAnalyticsRepositoryInterface
{
    /**
     * Get vendor's average price for product
     * 
     * @param string $vendorId Vendor identifier
     * @param string $productId Product identifier
     * @return float Average price (0 if no history)
     */
    public function getVendorAveragePrice(string $vendorId, string $productId): float;

    /**
     * Get vendor's price standard deviation for product
     * 
     * @param string $vendorId Vendor identifier
     * @param string $productId Product identifier
     * @return float Standard deviation (0 if no history)
     */
    public function getVendorStdPrice(string $vendorId, string $productId): float;

    /**
     * Get vendor's minimum price for product
     * 
     * @param string $vendorId Vendor identifier
     * @param string $productId Product identifier
     * @return float Minimum historical price (0 if no history)
     */
    public function getVendorMinPrice(string $vendorId, string $productId): float;

    /**
     * Get vendor's maximum price for product
     * 
     * @param string $vendorId Vendor identifier
     * @param string $productId Product identifier
     * @return float Maximum historical price (0 if no history)
     */
    public function getVendorMaxPrice(string $vendorId, string $productId): float;

    /**
     * Get market average price across all vendors
     * 
     * @param string $productId Product identifier
     * @return float Market average price (0 if no data)
     */
    public function getMarketAveragePrice(string $productId): float;

    /**
     * Get market price standard deviation
     * 
     * @param string $productId Product identifier
     * @return float Standard deviation (0 if no data)
     */
    public function getMarketStdPrice(string $productId): float;

    /**
     * Get category average price
     * 
     * @param string $categoryId Product category identifier
     * @return float Average price in category (0 if no data)
     */
    public function getCategoryAveragePrice(string $categoryId): float;

    /**
     * Get recent competitive quotes for product
     * 
     * @param string $productId Product identifier
     * @return array<array{vendor_id: string, price: float, date: string}> Recent quotes (last 90 days)
     */
    public function getRecentQuotesForProduct(string $productId): array;

    /**
     * Get price velocity (rate of change)
     * 
     * @param string $vendorId Vendor identifier
     * @param string $productId Product identifier
     * @return float Daily percentage change (0.01 = 1% per day increase)
     */
    public function getPriceVelocity(string $vendorId, string $productId): float;

    /**
     * Get seasonal price factor
     * 
     * @param string $productId Product identifier
     * @return float Seasonal multiplier (1.0 = no seasonal effect, 1.2 = 20% premium)
     */
    public function getSeasonalPriceFactor(string $productId): float;

    /**
     * Get contract price for vendor-product combination
     * 
     * @param string $vendorId Vendor identifier
     * @param string $productId Product identifier
     * @return float Contract price (0 if no contract)
     */
    public function getContractPrice(string $vendorId, string $productId): float;

    /**
     * Get volume discount threshold quantity
     * 
     * @param string $vendorId Vendor identifier
     * @param string $productId Product identifier
     * @return float Quantity threshold for volume discount (0 if no discount program)
     */
    public function getVolumeDiscountThreshold(string $vendorId, string $productId): float;

    /**
     * Calculate expected volume discount percentage
     * 
     * @param string $vendorId Vendor identifier
     * @param string $productId Product identifier
     * @param float $quantity Order quantity
     * @return float Expected discount as percentage (0.15 = 15% discount)
     */
    public function getExpectedVolumeDiscount(string $vendorId, string $productId, float $quantity): float;

    /**
     * Get currency volatility over period
     * 
     * @param string $currency Currency code (e.g., 'USD', 'EUR')
     * @return float Volatility coefficient (coefficient of variation)
     */
    public function getCurrencyVolatility(string $currency): float;

    /**
     * Get geographic price variance for product
     * 
     * @param string $productId Product identifier
     * @return float Price variance across regions (coefficient of variation)
     */
    public function getGeographicPriceVariance(string $productId): float;

    /**
     * Get expected price impact from payment terms
     * Longer payment terms typically result in higher prices
     * 
     * @param string $vendorId Vendor identifier
     * @param int $paymentTermDays Payment term in days
     * @return float Expected price impact as percentage (0.05 = 5% increase for extended terms)
     */
    public function getPaymentTermPriceImpact(string $vendorId, int $paymentTermDays): float;
}
