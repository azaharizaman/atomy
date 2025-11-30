<?php

declare(strict_types=1);

namespace Nexus\Sales\Contracts;

use Nexus\Uom\Contracts\QuantityInterface;

/**
 * Tax calculation service contract.
 */
interface TaxCalculatorInterface
{
    /**
     * Calculate tax amount for a line item.
     *
     * @param string $tenantId
     * @param string $productVariantId
     * @param float $lineSubtotal Pre-discount subtotal
     * @param string $customerId Customer entity ID
     * @param string $currencyCode
     * @return float Tax amount in order currency
     */
    public function calculateLineTax(
        string $tenantId,
        string $productVariantId,
        float $lineSubtotal,
        string $customerId,
        string $currencyCode
    ): float;

    /**
     * Get tax rate (percentage) for a product/customer combination.
     *
     * @param string $tenantId
     * @param string $productVariantId
     * @param string $customerId
     * @return float Tax rate as percentage (e.g., 6.00 for 6%)
     */
    public function getTaxRate(
        string $tenantId,
        string $productVariantId,
        string $customerId
    ): float;
}
