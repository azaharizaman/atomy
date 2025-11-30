<?php

declare(strict_types=1);

namespace Nexus\Procurement\Contracts;

/**
 * Purchase requisition line interface.
 */
interface RequisitionLineInterface
{
    /**
     * Get line number.
     *
     * @return int
     */
    public function getLineNumber(): int;

    /**
     * Get item description.
     *
     * @return string
     */
    public function getItemDescription(): string;

    /**
     * Get quantity.
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
     * Get unit price estimate.
     *
     * @return float
     */
    public function getUnitPriceEstimate(): float;

    /**
     * Get total estimate for this line.
     *
     * @return float
     */
    public function getTotalEstimate(): float;
}
