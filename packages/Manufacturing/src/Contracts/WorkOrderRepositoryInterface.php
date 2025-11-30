<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Nexus\Manufacturing\Exceptions\WorkOrderNotFoundException;

/**
 * Repository interface for Work Order persistence.
 *
 * Consumers must implement this interface to provide Work Order storage.
 */
interface WorkOrderRepositoryInterface
{
    /**
     * Find a work order by ID.
     *
     * @throws WorkOrderNotFoundException If work order not found
     */
    public function findById(string $id): WorkOrderInterface;

    /**
     * Find a work order by ID or return null.
     */
    public function findByIdOrNull(string $id): ?WorkOrderInterface;

    /**
     * Find a work order by order number.
     *
     * @throws WorkOrderNotFoundException If work order not found
     */
    public function findByOrderNumber(string $orderNumber): WorkOrderInterface;

    /**
     * Find work orders by status.
     *
     * @return array<WorkOrderInterface>
     */
    public function findByStatus(string $status): array;

    /**
     * Find work orders by product.
     *
     * @return array<WorkOrderInterface>
     */
    public function findByProduct(string $productId): array;

    /**
     * Find work orders scheduled between dates.
     *
     * @return array<WorkOrderInterface>
     */
    public function findByDateRange(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array;

    /**
     * Find work orders for a sales order.
     *
     * @return array<WorkOrderInterface>
     */
    public function findBySalesOrder(string $salesOrderId): array;

    /**
     * Find child work orders for a parent.
     *
     * @return array<WorkOrderInterface>
     */
    public function findByParent(string $parentWorkOrderId): array;

    /**
     * Save a work order (create or update).
     */
    public function save(WorkOrderInterface $workOrder): void;

    /**
     * Delete a work order by ID.
     */
    public function delete(string $id): void;

    /**
     * Generate next work order number.
     */
    public function getNextOrderNumber(): string;

    /**
     * Create a new work order from array data.
     *
     * @param array<string, mixed> $data Work order data
     */
    public function create(array $data): WorkOrderInterface;

    /**
     * Update an existing work order.
     *
     * @param string $workOrderId Work order ID
     * @param array<string, mixed> $data Data to update
     */
    public function update(string $workOrderId, array $data): void;

    /**
     * Find work order by order number.
     */
    public function findByNumber(string $number): ?WorkOrderInterface;
}
