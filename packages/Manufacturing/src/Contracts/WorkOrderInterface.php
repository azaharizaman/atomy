<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

/**
 * Contract for Work Order entity.
 *
 * Represents a production order to manufacture a specific quantity of a product.
 */
interface WorkOrderInterface
{
    /**
     * Get the unique identifier for this work order.
     */
    public function getId(): string;

    /**
     * Get the tenant identifier.
     */
    public function getTenantId(): string;

    /**
     * Get the work order number.
     */
    public function getOrderNumber(): string;

    /**
     * Get the product ID being manufactured.
     */
    public function getProductId(): string;

    /**
     * Get the BOM ID used for this work order.
     */
    public function getBomId(): string;

    /**
     * Get the routing ID used for this work order.
     */
    public function getRoutingId(): ?string;

    /**
     * Get the planned quantity to manufacture.
     */
    public function getPlannedQuantity(): float;

    /**
     * Get the completed quantity so far.
     */
    public function getCompletedQuantity(): float;

    /**
     * Get the scrapped quantity.
     */
    public function getScrappedQuantity(): float;

    /**
     * Get the unit of measure.
     */
    public function getUom(): string;

    /**
     * Get the current status.
     */
    public function getStatus(): \Nexus\Manufacturing\Enums\WorkOrderStatus;

    /**
     * Get the warehouse ID for finished goods.
     */
    public function getWarehouseId(): string;

    /**
     * Get the planned start date.
     */
    public function getPlannedStartDate(): \DateTimeImmutable;

    /**
     * Get the planned end date.
     */
    public function getPlannedEndDate(): \DateTimeImmutable;

    /**
     * Get the actual start date.
     */
    public function getActualStartDate(): ?\DateTimeImmutable;

    /**
     * Get the actual end date.
     */
    public function getActualEndDate(): ?\DateTimeImmutable;

    /**
     * Get work order lines (material requirements).
     *
     * @return array<WorkOrderLineInterface>
     */
    public function getLines(): array;

    /**
     * Get the priority (1 = highest).
     */
    public function getPriority(): int;

    /**
     * Get optional parent work order ID for sub-assemblies.
     */
    public function getParentWorkOrderId(): ?string;

    /**
     * Get optional sales order reference.
     */
    public function getSalesOrderId(): ?string;

    /**
     * Get optional notes.
     */
    public function getNotes(): ?string;

    /**
     * Get creation timestamp.
     */
    public function getCreatedAt(): \DateTimeImmutable;

    /**
     * Get last update timestamp.
     */
    public function getUpdatedAt(): \DateTimeImmutable;
}
