<?php

declare(strict_types=1);

namespace Nexus\Procurement\Contracts;

/**
 * Purchase order line interface.
 *
 * This interface MUST be compatible with Nexus\Payable\Contracts\PurchaseOrderLineInterface
 * to support 3-way matching operations.
 */
interface PurchaseOrderLineInterface
{
    /**
     * Get line number.
     *
     * @return int
     */
    public function getLineNumber(): int;

    /**
     * Get line reference for matching.
     *
     * Required by Nexus\Payable for 3-way matching.
     *
     * @return string e.g., "PO-2024-001-L1"
     */
    public function getLineReference(): string;

    /**
     * Get product variant ID (from Nexus\Product package).
     *
     * Null for legacy data migrated before product integration.
     *
     * @return string|null
     */
    public function getProductVariantId(): ?string;

    /**
     * Get item description.
     *
     * Fallback for display when product variant is not linked.
     *
     * @return string
     */
    public function getItemDescription(): string;

    /**
     * Get ordered quantity.
     *
     * Required by Nexus\Payable for 3-way matching.
     *
     * @return float
     */
    public function getQuantity(): float;

    /**
     * Get unit of measurement.
     *
     * @return string
     */
    public function getUom(): string;

    /**
     * Get unit price.
     *
     * Required by Nexus\Payable for 3-way matching.
     *
     * @return float
     */
    public function getUnitPrice(): float;

    /**
     * Get total amount for this line.
     *
     * @return float
     */
    public function getTotalAmount(): float;

    /**
     * Get received quantity.
     *
     * @return float
     */
    public function getReceivedQuantity(): float;
}
