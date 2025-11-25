<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Nexus\Manufacturing\Exceptions\RoutingNotFoundException;

/**
 * Repository interface for Routing persistence.
 *
 * Consumers must implement this interface to provide Routing storage.
 */
interface RoutingRepositoryInterface
{
    /**
     * Find a routing by ID.
     *
     * @throws RoutingNotFoundException If routing not found
     */
    public function findById(string $id): RoutingInterface;

    /**
     * Find a routing by ID or return null.
     */
    public function findByIdOrNull(string $id): ?RoutingInterface;

    /**
     * Find a routing by code.
     *
     * @throws RoutingNotFoundException If routing not found
     */
    public function findByCode(string $code): RoutingInterface;

    /**
     * Find the effective routing for a code at a given date.
     *
     * @throws RoutingNotFoundException If no effective routing found
     */
    public function findEffectiveByCode(string $code, \DateTimeImmutable $asOf): RoutingInterface;

    /**
     * Find all routing versions for a code.
     *
     * @return array<RoutingInterface>
     */
    public function findAllByCode(string $code): array;

    /**
     * Find the latest version routing for a code.
     */
    public function findLatestByCode(string $code): ?RoutingInterface;

    /**
     * Check if a routing exists for a code.
     */
    public function existsForCode(string $code): bool;

    /**
     * Save a routing (create or update).
     */
    public function save(RoutingInterface $routing): void;

    /**
     * Delete a routing by ID.
     */
    public function delete(string $id): void;

    /**
     * Get the next version number for a routing code.
     */
    public function getNextVersion(string $code): int;

    /**
     * Find all active routings.
     *
     * @return array<RoutingInterface>
     */
    public function findAllActive(): array;

    /**
     * Create a new routing from array data.
     *
     * @param array<string, mixed> $data Routing data
     */
    public function create(array $data): RoutingInterface;

    /**
     * Update an existing routing.
     *
     * @param string $routingId Routing ID
     * @param array<string, mixed> $data Data to update
     * @return RoutingInterface Updated routing
     */
    public function update(string $routingId, array $data): RoutingInterface;

    /**
     * Find routing by product ID and optional effective date.
     *
     * @param string $productId Product ID
     * @param \DateTimeImmutable|null $effectiveDate Date to check effectivity
     */
    public function findByProductId(string $productId, ?\DateTimeImmutable $effectiveDate = null): ?RoutingInterface;

    /**
     * Find all versions of routings for a product.
     *
     * @return array<RoutingInterface>
     */
    public function findAllVersions(string $productId): array;
}
