<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\Contracts;

/**
 * Historical data repository interface for ML features
 * 
 * Provides statistical aggregations of historical purchase data.
 */
interface HistoricalDataRepositoryInterface
{
    /**
     * Get average quantity for product
     * 
     * @param string $productId Product variant identifier
     * @return float Average quantity (0 if no history)
     */
    public function getAverageQty(string $productId): float;

    /**
     * Get standard deviation of quantity for product
     * 
     * @param string $productId Product variant identifier
     * @return float Standard deviation (0 if no history)
     */
    public function getStdQty(string $productId): float;

    /**
     * Get average unit price for product
     * 
     * @param string $productId Product variant identifier
     * @return float Average price (0 if no history)
     */
    public function getAveragePrice(string $productId): float;

    /**
     * Get standard deviation of price for product
     * 
     * @param string $productId Product variant identifier
     * @return float Standard deviation (0 if no history)
     */
    public function getStdPrice(string $productId): float;

    /**
     * Get vendor average quantity for specific product
     * 
     * @param string $productId Product variant identifier
     * @param string $vendorId Vendor party identifier
     * @return float Average quantity from this vendor (0 if no history)
     */
    public function getVendorAverageQty(string $productId, string $vendorId): float;

    /**
     * Get total transaction count for vendor
     * 
     * @param string $vendorId Vendor party identifier
     * @return int Number of completed POs with this vendor
     */
    public function getTransactionCountByVendor(string $vendorId): int;

    /**
     * Get days since last order for product
     * 
     * @param string $productId Product variant identifier
     * @return int Days since last PO (999 if never ordered)
     */
    public function getDaysSinceLastOrder(string $productId): int;
}
