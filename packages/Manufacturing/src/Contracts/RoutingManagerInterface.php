<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Nexus\Manufacturing\Exceptions\RoutingNotFoundException;
use Nexus\Manufacturing\Exceptions\InvalidRoutingVersionException;
use Nexus\Manufacturing\ValueObjects\Operation;

/**
 * Manager interface for Routing operations.
 *
 * Provides business logic layer for manufacturing routing management.
 */
interface RoutingManagerInterface
{
    /**
     * Create a new routing for a product.
     *
     * @param array<Operation> $operations Initial operations
     */
    public function create(
        string $productId,
        string $version,
        array $operations = [],
        ?\DateTimeImmutable $effectiveFrom = null,
        ?\DateTimeImmutable $effectiveTo = null
    ): RoutingInterface;

    /**
     * Get a routing by ID.
     *
     * @throws RoutingNotFoundException If routing not found
     */
    public function getById(string $id): RoutingInterface;

    /**
     * Get the effective routing for a product at a point in time.
     *
     * Returns the active, released routing version effective at the given date.
     *
     * @throws RoutingNotFoundException If no effective routing found
     */
    public function getEffective(string $productId, ?\DateTimeImmutable $asOfDate = null): RoutingInterface;

    /**
     * Create a new version of an existing routing.
     *
     * Copies the structure from the source routing to a new version.
     *
     * @throws RoutingNotFoundException If source routing not found
     * @throws InvalidRoutingVersionException If version already exists
     */
    public function createVersion(
        string $sourceRoutingId,
        string $newVersion,
        ?\DateTimeImmutable $effectiveFrom = null
    ): RoutingInterface;

    /**
     * Release a routing for production use.
     *
     * @throws RoutingNotFoundException If routing not found
     */
    public function release(string $routingId): void;

    /**
     * Obsolete a routing (no longer available for new production).
     *
     * @throws RoutingNotFoundException If routing not found
     */
    public function obsolete(string $routingId): void;

    /**
     * Add an operation to a routing.
     *
     * @throws RoutingNotFoundException If routing not found
     */
    public function addOperation(string $routingId, Operation $operation): void;

    /**
     * Update an operation in a routing.
     *
     * @throws RoutingNotFoundException If routing not found
     */
    public function updateOperation(string $routingId, int $operationNumber, Operation $operation): void;

    /**
     * Remove an operation from a routing.
     *
     * @throws RoutingNotFoundException If routing not found
     */
    public function removeOperation(string $routingId, int $operationNumber): void;

    /**
     * Calculate total lead time for a routing.
     *
     * Includes setup, run, and queue times for all operations.
     *
     * @param float $quantity Quantity to calculate for
     * @return float Total lead time in hours
     */
    public function calculateLeadTime(string $routingId, float $quantity): float;

    /**
     * Calculate total cost for a routing.
     *
     * Includes labor, machine, and overhead costs.
     *
     * @param float $quantity Quantity to calculate for
     * @return array{labor: float, machine: float, overhead: float, total: float}
     */
    public function calculateCost(string $routingId, float $quantity): array;

    /**
     * Validate routing structure.
     *
     * @return array<string> List of validation errors (empty if valid)
     */
    public function validate(string $routingId): array;
}
