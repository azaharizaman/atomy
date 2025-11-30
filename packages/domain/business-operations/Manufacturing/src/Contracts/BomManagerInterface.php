<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Nexus\Manufacturing\Exceptions\BomNotFoundException;
use Nexus\Manufacturing\Exceptions\InvalidBomVersionException;
use Nexus\Manufacturing\ValueObjects\BomLine;

/**
 * Manager interface for BOM operations.
 *
 * Provides business logic layer for Bill of Materials management.
 */
interface BomManagerInterface
{
    /**
     * Create a new BOM for a product.
     *
     * @param array<BomLine> $lines Initial BOM lines
     */
    public function create(
        string $productId,
        string $version,
        string $type,
        array $lines = [],
        ?\DateTimeImmutable $effectiveFrom = null,
        ?\DateTimeImmutable $effectiveTo = null
    ): BomInterface;

    /**
     * Get a BOM by ID.
     *
     * @throws BomNotFoundException If BOM not found
     */
    public function getById(string $id): BomInterface;

    /**
     * Get the effective BOM for a product at a point in time.
     *
     * Returns the active, released BOM version effective at the given date.
     *
     * @throws BomNotFoundException If no effective BOM found
     */
    public function getEffective(string $productId, ?\DateTimeImmutable $asOfDate = null): BomInterface;

    /**
     * Create a new version of an existing BOM.
     *
     * Copies the structure from the source BOM to a new version.
     *
     * @throws BomNotFoundException If source BOM not found
     * @throws InvalidBomVersionException If version already exists
     */
    public function createVersion(
        string $sourceBomId,
        string $newVersion,
        ?\DateTimeImmutable $effectiveFrom = null
    ): BomInterface;

    /**
     * Release a BOM for production use.
     *
     * @throws BomNotFoundException If BOM not found
     */
    public function release(string $bomId): void;

    /**
     * Obsolete a BOM (no longer available for new production).
     *
     * @throws BomNotFoundException If BOM not found
     */
    public function obsolete(string $bomId): void;

    /**
     * Add a line item to a BOM.
     *
     * @throws BomNotFoundException If BOM not found
     */
    public function addLine(string $bomId, BomLine $line): void;

    /**
     * Update a line item in a BOM.
     *
     * @throws BomNotFoundException If BOM not found
     */
    public function updateLine(string $bomId, int $lineNumber, BomLine $line): void;

    /**
     * Remove a line item from a BOM.
     *
     * @throws BomNotFoundException If BOM not found
     */
    public function removeLine(string $bomId, int $lineNumber): void;

    /**
     * Find a BOM by product ID.
     *
     * Returns the active/effective BOM for the product.
     *
     * @throws BomNotFoundException If no BOM found for product
     */
    public function findByProductId(string $productId): BomInterface;

    /**
     * Explode a BOM to get all components recursively.
     *
     * Returns flat list of all components with calculated quantities.
     *
     * @param float $parentQuantity Quantity to explode for
     * @return array<array{productId: string, quantity: float, level: int, uomCode: string}>
     */
    public function explode(string $bomId, float $parentQuantity = 1.0): array;

    /**
     * Check where a component is used (reverse BOM lookup).
     *
     * @return array<array{bomId: string, productId: string, quantity: float}>
     */
    public function whereUsed(string $componentProductId): array;

    /**
     * Validate BOM structure (circular references, missing products, etc.).
     *
     * @return array<string> List of validation errors (empty if valid)
     */
    public function validate(string $bomId): array;
}
