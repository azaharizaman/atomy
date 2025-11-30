<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

/**
 * Contract for Work Order Line entity.
 *
 * Represents a material requirement or operation tracking for a work order.
 */
interface WorkOrderLineInterface
{
    /**
     * Get the unique identifier for this line.
     */
    public function getId(): string;

    /**
     * Get the parent work order ID.
     */
    public function getWorkOrderId(): string;

    /**
     * Get the line number/sequence.
     */
    public function getLineNumber(): int;

    /**
     * Get the line type (material, operation).
     */
    public function getLineType(): string;

    /**
     * Get the component product ID (for material lines).
     */
    public function getComponentProductId(): ?string;

    /**
     * Get the operation ID (for operation lines).
     */
    public function getOperationId(): ?string;

    /**
     * Get the planned quantity.
     */
    public function getPlannedQuantity(): float;

    /**
     * Get the actual consumed/completed quantity.
     */
    public function getActualQuantity(): float;

    /**
     * Get the unit of measure.
     */
    public function getUom(): string;

    /**
     * Get the warehouse ID for material sourcing.
     */
    public function getWarehouseId(): ?string;

    /**
     * Get the lot ID if lot-tracked.
     */
    public function getLotId(): ?string;

    /**
     * Get the serial number if serial-tracked.
     */
    public function getSerialNumber(): ?string;

    /**
     * Get the reservation ID from inventory.
     */
    public function getReservationId(): ?string;

    /**
     * Check if this line is fully consumed/completed.
     */
    public function isComplete(): bool;

    /**
     * Get the variance (actual - planned).
     */
    public function getVariance(): float;
}
