<?php

declare(strict_types=1);

namespace Nexus\Procurement\Contracts;

/**
 * Goods receipt line interface.
 *
 * This interface MUST be compatible with Nexus\Payable\Contracts\GoodsReceivedLineInterface
 * to support 3-way matching operations.
 */
interface GoodsReceiptLineInterface
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
     * @return string e.g., "GRN-2024-001-L1"
     */
    public function getLineReference(): string;

    /**
     * Get PO line reference.
     *
     * Links this GRN line back to the original PO line.
     *
     * @return string PO line reference
     */
    public function getPoLineReference(): string;

    /**
     * Get received quantity.
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
     * Get item description.
     *
     * @return string
     */
    public function getItemDescription(): string;
}
