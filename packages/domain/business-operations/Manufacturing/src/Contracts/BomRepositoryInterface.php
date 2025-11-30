<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Nexus\Manufacturing\Exceptions\BomNotFoundException;

/**
 * Repository interface for BOM persistence.
 *
 * Consumers must implement this interface to provide BOM storage.
 */
interface BomRepositoryInterface
{
    /**
     * Find a BOM by ID.
     *
     * @throws BomNotFoundException If BOM not found
     */
    public function findById(string $id): BomInterface;

    /**
     * Find a BOM by ID or return null.
     */
    public function findByIdOrNull(string $id): ?BomInterface;

    /**
     * Find the effective BOM for a product at a given date.
     *
     * @throws BomNotFoundException If no effective BOM found
     */
    public function findEffectiveByProduct(string $productId, \DateTimeImmutable $asOf): BomInterface;

    /**
     * Find all BOMs for a product (all versions).
     *
     * @return array<BomInterface>
     */
    public function findAllByProduct(string $productId): array;

    /**
     * Find the latest version BOM for a product.
     */
    public function findLatestByProduct(string $productId): ?BomInterface;

    /**
     * Check if a BOM exists for a product.
     */
    public function existsForProduct(string $productId): bool;

    /**
     * Save a BOM (create or update).
     */
    public function save(BomInterface $bom): void;

    /**
     * Delete a BOM by ID.
     */
    public function delete(string $id): void;

    /**
     * Get the next version number for a product's BOM.
     */
    public function getNextVersion(string $productId): int;

    /**
     * Create a new BOM from array data.
     *
     * @param array<string, mixed> $data BOM data
     */
    public function create(array $data): BomInterface;

    /**
     * Update an existing BOM.
     *
     * @param string $bomId BOM ID
     * @param array<string, mixed> $data Data to update
     */
    public function update(string $bomId, array $data): void;

    /**
     * Find BOM by product ID and optional effective date.
     *
     * @param string $productId Product ID
     * @param \DateTimeImmutable|null $effectiveDate Date to check effectivity
     */
    public function findByProductId(string $productId, ?\DateTimeImmutable $effectiveDate = null): ?BomInterface;

    /**
     * Find all versions of BOMs for a product.
     *
     * @return array<BomInterface>
     */
    public function findAllVersions(string $productId): array;

    /**
     * Find where a component is used (reverse BOM lookup).
     *
     * @return array<array{bomId: string, productId: string, quantity: float}>
     */
    public function findWhereUsed(string $componentProductId): array;
}
