<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Nexus\Manufacturing\Exceptions\WorkOrderNotFoundException;
use Nexus\Manufacturing\Exceptions\InvalidWorkOrderStatusException;
use Nexus\Manufacturing\ValueObjects\WorkOrderLine;
use Nexus\Manufacturing\ValueObjects\OperationCompletion;

/**
 * Manager interface for Work Order operations.
 *
 * Provides business logic layer for production work order management.
 */
interface WorkOrderManagerInterface
{
    /**
     * Create a new work order from a BOM and routing.
     */
    public function create(
        string $productId,
        float $quantity,
        \DateTimeImmutable $plannedStartDate,
        \DateTimeImmutable $plannedEndDate,
        ?string $bomId = null,
        ?string $routingId = null,
        ?string $sourceReference = null
    ): WorkOrderInterface;

    /**
     * Get a work order by ID.
     *
     * @throws WorkOrderNotFoundException If work order not found
     */
    public function getById(string $id): WorkOrderInterface;

    /**
     * Get a work order by number.
     *
     * @throws WorkOrderNotFoundException If work order not found
     */
    public function getByNumber(string $number): WorkOrderInterface;

    /**
     * Release a work order for production.
     *
     * This will:
     * - Validate material availability
     * - Reserve inventory
     * - Make work order available for shop floor
     *
     * @throws WorkOrderNotFoundException If work order not found
     * @throws InvalidWorkOrderStatusException If not in valid state
     */
    public function release(string $workOrderId): void;

    /**
     * Start production on a work order.
     *
     * @throws WorkOrderNotFoundException If work order not found
     * @throws InvalidWorkOrderStatusException If not in valid state
     */
    public function start(string $workOrderId, ?\DateTimeImmutable $actualStartDate = null): void;

    /**
     * Report operation completion.
     *
     * Records labor time, machine time, quantities produced.
     *
     * @throws WorkOrderNotFoundException If work order not found
     */
    public function reportOperation(string $workOrderId, OperationCompletion $completion): void;

    /**
     * Report material consumption.
     *
     * Records actual material used (may differ from planned).
     *
     * @throws WorkOrderNotFoundException If work order not found
     */
    public function reportMaterialConsumption(
        string $workOrderId,
        string $productId,
        float $quantity,
        ?string $lotNumber = null,
        ?string $warehouseId = null
    ): void;

    /**
     * Report production output.
     *
     * Records finished goods produced.
     *
     * @throws WorkOrderNotFoundException If work order not found
     */
    public function reportOutput(
        string $workOrderId,
        float $quantity,
        float $scrapQuantity = 0.0,
        ?string $warehouseId = null
    ): void;

    /**
     * Complete a work order.
     *
     * This will:
     * - Validate all materials consumed
     * - Update inventory with produced quantity
     * - Calculate actual costs
     *
     * @throws WorkOrderNotFoundException If work order not found
     * @throws InvalidWorkOrderStatusException If not in valid state
     */
    public function complete(string $workOrderId, ?\DateTimeImmutable $actualEndDate = null): void;

    /**
     * Close a work order (final accounting closure).
     *
     * Posts variances between standard and actual costs.
     *
     * @throws WorkOrderNotFoundException If work order not found
     * @throws InvalidWorkOrderStatusException If not in valid state
     */
    public function close(string $workOrderId): void;

    /**
     * Cancel a work order.
     *
     * Releases any reserved inventory.
     *
     * @throws WorkOrderNotFoundException If work order not found
     * @throws InvalidWorkOrderStatusException If not in valid state
     */
    public function cancel(string $workOrderId, string $reason): void;

    /**
     * Split a work order into multiple orders.
     *
     * Useful when only partial quantity can be produced.
     *
     * @param float $splitQuantity Quantity for the new work order
     * @throws WorkOrderNotFoundException If work order not found
     */
    public function split(string $workOrderId, float $splitQuantity): WorkOrderInterface;

    /**
     * Calculate work order cost variance.
     *
     * @return array{material: float, labor: float, overhead: float, total: float}
     */
    public function calculateVariance(string $workOrderId): array;

    /**
     * Get work order status summary.
     *
     * @return array{
     *     completedOperations: int,
     *     totalOperations: int,
     *     producedQuantity: float,
     *     plannedQuantity: float,
     *     scrapQuantity: float,
     *     percentComplete: float
     * }
     */
    public function getProgress(string $workOrderId): array;
}
