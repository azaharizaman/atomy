<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

/**
 * Inventory Data Provider interface.
 *
 * Provides inventory data for MRP calculations.
 * Consumers must implement this interface to provide inventory information.
 */
interface InventoryDataProviderInterface
{
    /**
     * Get on-hand quantity for a product.
     *
     * @param string $productId Product ID
     * @return float Current on-hand quantity
     */
    public function getOnHandQuantity(string $productId): float;

    /**
     * Get safety stock level for a product.
     *
     * @param string $productId Product ID
     * @return float Safety stock quantity
     */
    public function getSafetyStock(string $productId): float;

    /**
     * Get scheduled receipts for a product up to a date.
     *
     * @param string $productId Product ID
     * @param \DateTimeImmutable $untilDate Date to check until
     * @return array<array{date: \DateTimeImmutable, quantity: float}>
     */
    public function getScheduledReceipts(string $productId, \DateTimeImmutable $untilDate): array;

    /**
     * Get lead time in days for a product.
     *
     * @param string $productId Product ID
     * @return int Lead time in days
     */
    public function getLeadTimeDays(string $productId): int;

    /**
     * Get reserved quantity for a product.
     *
     * @param string $productId Product ID
     * @return float Reserved quantity
     */
    public function getReservedQuantity(string $productId): float;

    /**
     * Check if product is purchasable or manufacturable.
     *
     * @param string $productId Product ID
     * @return string 'purchase' or 'manufacture'
     */
    public function getReplenishmentType(string $productId): string;
}
