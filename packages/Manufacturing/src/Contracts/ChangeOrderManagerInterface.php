<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Nexus\Manufacturing\Exceptions\ChangeOrderNotFoundException;
use Nexus\Manufacturing\Exceptions\InvalidChangeOrderStatusException;

/**
 * Manager interface for Engineering Change Order operations.
 *
 * Provides business logic layer for managing engineering changes
 * that affect BOMs and Routings with effectivity date control.
 */
interface ChangeOrderManagerInterface
{
    /**
     * Create a new engineering change order.
     *
     * @param array<string> $affectedBomIds BOM IDs affected by this change
     * @param array<string> $affectedRoutingIds Routing IDs affected by this change
     */
    public function create(
        string $productId,
        string $description,
        array $affectedBomIds = [],
        array $affectedRoutingIds = [],
        ?\DateTimeImmutable $effectiveDate = null
    ): ChangeOrderInterface;

    /**
     * Get a change order by ID.
     *
     * @throws ChangeOrderNotFoundException If change order not found
     */
    public function getById(string $id): ChangeOrderInterface;

    /**
     * Get a change order by number.
     *
     * @throws ChangeOrderNotFoundException If change order not found
     */
    public function getByNumber(string $number): ChangeOrderInterface;

    /**
     * Add a BOM change to the change order.
     *
     * @param array{action: string, bomId: string, changes: array<string, mixed>} $change
     * @throws ChangeOrderNotFoundException If change order not found
     */
    public function addBomChange(string $changeOrderId, array $change): void;

    /**
     * Add a routing change to the change order.
     *
     * @param array{action: string, routingId: string, changes: array<string, mixed>} $change
     * @throws ChangeOrderNotFoundException If change order not found
     */
    public function addRoutingChange(string $changeOrderId, array $change): void;

    /**
     * Submit change order for approval.
     *
     * @throws ChangeOrderNotFoundException If change order not found
     * @throws InvalidChangeOrderStatusException If not in valid state
     */
    public function submit(string $changeOrderId): void;

    /**
     * Approve a change order.
     *
     * @throws ChangeOrderNotFoundException If change order not found
     * @throws InvalidChangeOrderStatusException If not in valid state
     */
    public function approve(string $changeOrderId, string $approvedBy): void;

    /**
     * Reject a change order.
     *
     * @throws ChangeOrderNotFoundException If change order not found
     * @throws InvalidChangeOrderStatusException If not in valid state
     */
    public function reject(string $changeOrderId, string $rejectedBy, string $reason): void;

    /**
     * Implement an approved change order.
     *
     * This will:
     * - Create new BOM versions with effectivity dates
     * - Create new routing versions with effectivity dates
     * - Set obsolete dates on old versions
     *
     * @throws ChangeOrderNotFoundException If change order not found
     * @throws InvalidChangeOrderStatusException If not approved
     */
    public function implement(string $changeOrderId): void;

    /**
     * Cancel a change order.
     *
     * @throws ChangeOrderNotFoundException If change order not found
     * @throws InvalidChangeOrderStatusException If already implemented
     */
    public function cancel(string $changeOrderId, string $reason): void;

    /**
     * Get all pending change orders for a product.
     *
     * @return array<ChangeOrderInterface>
     */
    public function getPendingForProduct(string $productId): array;

    /**
     * Get change order history for a product.
     *
     * @return array<ChangeOrderInterface>
     */
    public function getHistory(string $productId): array;

    /**
     * Validate change order can be implemented.
     *
     * @return array<string> List of validation errors (empty if valid)
     */
    public function validate(string $changeOrderId): array;
}
